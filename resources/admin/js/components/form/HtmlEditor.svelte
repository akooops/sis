<script>
    /**
     * HtmlEditor — TinyMCE 8 (self-hosted, GPL) as a Svelte 5 rune component.
     * `value` is the HTML string, two-way bound.
     *
     *   <HtmlEditor bind:value={form.data.content.en} rtl ready={formReady} />
     *
     * Everything TinyMCE would otherwise fetch at runtime — theme, model, icons,
     * skin css, content css, plugins — is imported below and re-exposed via
     * skin_url/content_css: 'default', so Vite's hashed build never 404s and there
     * is no base_url to configure. Every plugin named in `plugins` MUST have a
     * matching import here or it 404s at runtime.
     *
     * Security: the `code` plugin lets an admin paste raw <script> into content a
     * public renderer would emit. Only approved admins reach this screen and every
     * save is audited, so that is acceptable BY DESIGN — but if the renderer ever
     * becomes untrusted, sanitise server-side. Never rely on the editor alone.
     */
    import tinymce from 'tinymce';

    import 'tinymce/icons/default/icons.min.js';
    import 'tinymce/themes/silver/theme.min.js';
    import 'tinymce/models/dom/model.min.js';

    import 'tinymce/skins/ui/oxide/skin.js';
    import 'tinymce/skins/ui/oxide/content.js';
    import 'tinymce/skins/content/default/content.js';

    import 'tinymce/plugins/advlist';
    import 'tinymce/plugins/anchor';
    import 'tinymce/plugins/autolink';
    import 'tinymce/plugins/charmap';
    import 'tinymce/plugins/code';
    import 'tinymce/plugins/directionality';
    import 'tinymce/plugins/fullscreen';
    import 'tinymce/plugins/image';
    import 'tinymce/plugins/link';
    import 'tinymce/plugins/lists';
    import 'tinymce/plugins/preview';
    import 'tinymce/plugins/searchreplace';
    import 'tinymce/plugins/table';
    import 'tinymce/plugins/visualblocks';
    import 'tinymce/plugins/wordcount';

    import { untrack } from 'svelte';
    import { acceptForTypes, typeForFile, uploadFile } from '@/lib/upload';

    let {
        value = $bindable(''),
        rtl = false,
        // False while a container transition is running. IndexCard's fly puts a
        // transform on the panel for 750ms, which skews every measurement taken
        // inside it — so the editor waits rather than initialising crooked.
        ready = true,
        contentCssUrl = null,
        contentStyle = '',
        /*
         * The id put on the editor document's <body>, so the admin's own CSS
         * matches in here exactly as it will on the site.
         *
         * The public content div carries id="page-content" and every CSS hint in
         * the admin tells the author to scope their rules to it — so without this
         * the editor would load their stylesheet and then match none of it, and
         * the preview would say their CSS does nothing. TinyMCE's default body id
         * is `tinymce`, which no rule of theirs will ever name.
         */
        bodyId = 'page-content',
        height = 480,
        disabled = false,
        // Opt-in extras, both off so no existing caller changes behaviour.
        // Let the link dialog upload a document and link to it.
        fileUpload = false,
        // Tokens the caller lets the admin drop into the body, as
        // [{ label, value, html? }]. Non-empty adds ONE "Placeholders" menu, so a
        // further token is one array entry rather than another boolean prop.
        placeholders = [],
    } = $props();

    // Two nested divs on purpose: TinyMCE replaces `target` and restores it on
    // remove(), while Svelte only ever unmounts `host`. Neither library is
    // surprised by the other's DOM.
    let host;
    // $state so the init effect re-runs the moment bind:this lands, rather than
    // relying on mount ordering to have assigned it by the first run.
    let target = $state(null);
    let editor = $state(null);

    // Guards the write-back loop: set before setContent, so when the resulting
    // SetContent event reassigns `value` the sync effect sees no change and
    // leaves the caret alone.
    let lastEmitted = '';

    async function handleUpload(blobInfo) {
        const blob = blobInfo.blob();
        const file = new File([blob], blobInfo.filename(), { type: blob.type });

        try {
            const media = await uploadFile(file, 'images');

            // media.url is null until ScanUpload promotes the file to the public
            // disk. With the default null scanner that has already happened; with
            // clamav on a real queue it has not. Refuse rather than insert an
            // empty src.
            if (!media?.url) {
                return Promise.reject({
                    message: 'The image is still being scanned. Try again in a moment.',
                    remove: true,
                });
            }


            return media.url;
        } catch (e) {
            return Promise.reject({ message: e?.message ?? 'Upload failed.', remove: true });
        }
    }

    /** The Insert-image dialog's Browse button, routed through the same upload path. */
    function pickImage(callback) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async () => {
            const file = input.files?.[0];
            if (!file) return;
            try {
                const media = await uploadFile(file, 'images');
                if (media?.url) callback(media.url, { title: media.name });
            } catch {
                // The dialog stays open; the admin can retry or cancel.
            }
        };
        input.click();
    }

    /**
     * The link dialog's Browse button (fileUpload only) — attach a document and
     * link to it. Images are allowed too, so `typeForFile` resolves which type to
     * upload as: hardcoding 'documents' would make uploadFile reject a PNG the
     * picker had just offered.
     */
    function pickDocument(callback) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = acceptForTypes(['documents', 'images']);
        input.onchange = async () => {
            const file = input.files?.[0];
            if (!file) return;
            try {
                const media = await uploadFile(file, typeForFile(file.name, ['documents', 'images']) ?? 'documents');
                // Same guard as the image path: a file still being scanned has no
                // url yet, and an empty href is worse than no link.
                if (media?.url) callback(media.url, { text: media.name });
            } catch {
                // The dialog stays open; the admin can retry or cancel.
            }
        };
        input.click();
    }

    /** TinyMCE routes every Browse button here; `meta.filetype` says which dialog asked. */
    function pickMedia(callback, value_, meta) {
        if (meta?.filetype === 'file') pickDocument(callback);
        else pickImage(callback);
    }

    const BASE_TOOLBAR =
        'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | ltr rtl | link image table | removeformat code preview fullscreen';

    // Only names the button when there is at least one token behind it.
    const toolbar = $derived(placeholders.length ? `${BASE_TOOLBAR} | placeholders` : BASE_TOOLBAR);

    // Init + teardown. rtl/height/css are read through untrack() so they seed the
    // first paint without becoming dependencies — otherwise flipping RTL would
    // rebuild the editor, which is the whole thing this design avoids. Only
    // `ready` and `target` are dependencies.
    $effect(() => {
        if (!ready || !target) return;

        let cancelled = false;
        let instance = null;

        // Captured BEFORE init. TinyMCE fires SetContent during initialisation
        // with the target element's content — which is empty — so anything that
        // writes back to `value` from a listener registered in setup() would blank
        // the bound field before we ever get to seed it. Hence: read the value up
        // front, seed after init resolves, and only THEN start listening.
        const initial = untrack(() => value ?? '');

        tinymce
            .init({
                target,
                license_key: 'gpl',
                skin_url: 'default',
                // Only the bundled base css here. The page's own stylesheet and
                // inline CSS are injected into the editor document by the effect
                // below instead — content_css/content_style are init-only, and
                // going through the live path means a class you type in the source
                // view is styled the moment you save the CSS field, not on reload.
                content_css: 'default',
                // Init-only, unlike the CSS itself: an id is not something a
                // caller flips mid-edit, and TinyMCE offers no setter for it.
                body_id: untrack(() => bodyId),
                height: untrack(() => height),
                directionality: untrack(() => (rtl ? 'rtl' : 'ltr')),

                // TinyMCE validates against the HTML5 schema, where <style> is a
                // <head> element — so a <style> block typed into the source view is
                // body content with no valid parent, and it is silently dropped on
                // OK. Naming it a legal child of body is the whole fix. Inline
                // style="" attributes were never affected.
                valid_children: '+body[style]',
                extended_valid_elements: 'style[type|media]',

                promotion: false,
                branding: false,
                menubar: false,
                // The one piece of chrome that lives inside .tox-tinymce and uses
                // fixed-style positioning, so a transformed ancestor captures it.
                toolbar_sticky: false,
                toolbar_mode: 'sliding',
                plugins:
                    'advlist anchor autolink charmap code directionality fullscreen image link lists preview searchreplace table visualblocks wordcount',
                toolbar: untrack(() => toolbar),

                automatic_uploads: true,
                images_upload_handler: handleUpload,
                file_picker_types: untrack(() => (fileUpload ? 'image file' : 'image')),
                file_picker_callback: pickMedia,

                setup: (ed) => {
                    if (untrack(() => disabled)) ed.mode.set('readonly');

                    // Registered only when asked for, or the toolbar would name a
                    // button that does not exist.
                    const tokens = untrack(() => placeholders);

                    if (tokens.length) {
                        ed.ui.registry.addMenuButton('placeholders', {
                            text: 'Placeholders',
                            tooltip: 'Insert a placeholder — each recipient gets their own value',
                            fetch: (callback) =>
                                callback(
                                    tokens.map((token) => ({
                                        type: 'menuitem',
                                        text: token.label,
                                        // `html` for a token that must land as markup (a link); the bare token otherwise.
                                        onAction: () => ed.insertContent(token.html ?? token.value),
                                    })),
                                ),
                        });
                    }
                },
            })
            .then(([ed]) => {
                if (cancelled) {
                    try {
                        ed?.remove();
                    } catch {
                        // already torn down
                    }

                    return;
                }
                instance = ed;

                // Seed first, then read back what TinyMCE normalised it to, so
                // `value` holds exactly what a save would send and the sync effect
                // below sees no difference to act on.
                ed.setContent(initial);
                lastEmitted = ed.getContent();
                value = lastEmitted;

                // Only now does typing start writing back.
                ed.on('Change Input Undo Redo SetContent', () => {
                    lastEmitted = ed.getContent();
                    value = lastEmitted;
                });

                editor = ed;
            });

        // IndexCard unmounts the form with {#if showForm}, so this runs on every
        // Cancel/Save. remove() detaches the iframe, the body-level
        // .tox-tinymce-aux and every listener; without it each open leaks an editor.
        return () => {
            cancelled = true;
            try {
                instance?.remove();
            } catch {
                // Svelte may already have detached the host node.
            }
            instance = null;
            editor = null;
        };
    });

    // External value change — i.e. the language tab swapped. One instance and a
    // setContent, rather than {#key locale}: rebuilding TinyMCE per tab click
    // costs ~200ms, throws away the undo stack, and puts correctness on a
    // sync-during-teardown.
    $effect(() => {
        const next = value ?? '';
        if (!editor) return;
        if (next === lastEmitted) return;
        lastEmitted = next;
        editor.setContent(next);
        // A locale swap is a new document, not an undoable edit.
        editor.undoManager.clear();
    });

    // Content direction only. The toolbar stays LTR: CLAUDE.md fixes the admin
    // chrome to <html dir="ltr"> and there is no i18n layer, so only the authored
    // text flips. `dir` lives on the iframe body and survives setContent, which
    // rewrites innerHTML rather than the body's attributes.
    $effect(() => {
        const body = editor?.getBody();
        if (body) body.dir = rtl ? 'rtl' : 'ltr';
    });

    // The page's CSS, live. Written straight into the editor document's head and
    // keyed by a data attribute so repeat runs are idempotent — this is what makes
    // `<div class="promo">` typed in the source view render styled immediately.
    $effect(() => {
        const doc = editor?.getDoc();
        if (!doc) return;
        const head = doc.head;

        let link = head.querySelector('link[data-page-css]');
        if (contentCssUrl) {
            if (!link) {
                link = doc.createElement('link');
                link.rel = 'stylesheet';
                link.setAttribute('data-page-css', '');
                head.appendChild(link);
            }
            if (link.getAttribute('href') !== contentCssUrl) link.setAttribute('href', contentCssUrl);
        } else {
            link?.remove();
        }

        let style = head.querySelector('style[data-page-css]');
        if (contentStyle) {
            if (!style) {
                style = doc.createElement('style');
                style.setAttribute('data-page-css', '');
                head.appendChild(style);
            }
            if (style.textContent !== contentStyle) style.textContent = contentStyle;
        } else {
            style?.remove();
        }
    });
</script>

<div bind:this={host} class="overflow-hidden rounded-lg border border-border" class:opacity-60={!ready}>
    <div bind:this={target}></div>
</div>
