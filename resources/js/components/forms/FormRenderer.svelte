<script>
    /**
     * The whole form. Used THREE times over the same code:
     *   - the builder's live preview (mode="preview")
     *   - the builder canvas's read-only rendering
     *   - the public page's island (mode="live")
     *
     * Anything that would make those diverge belongs outside this component.
     * `mode` is the only branch, and it decides exactly two things: whether
     * submitting does anything, and whether navigation is real.
     *
     * NAVIGATION IS A STACK, not an index. "Previous" and an instruction page's
     * "back" are the same operation — pop — so they cannot disagree about where
     * back means. A `goto` button pushes the page it left, which is what lets an
     * instruction page be entered from anywhere and return to the right place.
     *
     * Imports nothing from Inertia, Ziggy or the API client.
     */
    import { tick } from 'svelte';
    import FormPage from './FormPage.svelte';
    import { translate, directionFor } from '@/lib/forms/i18n';
    import { emptyValue, ELEMENTS } from '@/lib/forms/elements';

    let {
        schema,
        /*
         * INITIAL values only — not a live binding.
         *
         * This was a $bindable, and it silently did not work: a $bindable passed
         * a plain object is not a $state proxy, so `values[key] = x` mutates it
         * without notifying anything. The hidden mirrors below never updated and
         * a native submit posted no answers at all.
         *
         * Answers live in internal $state instead, and leave through onsubmit /
         * oninteract. One owner, and reactivity that actually fires.
         */
        values = {},
        errors = {},
        locale = null,
        mode = 'live',
        submitting = false,
        // Posted natively rather than by fetch, so a validation failure comes
        // back through Laravel's ordinary withErrors/old-input path.
        action = null,
        csrf = null,
        token = null,
        honeypot = null,
        uploadAction = null,
        /*
         * { provider, siteKey, version } or null.
         *
         * Null is the common case AND the one that must never break: no widget,
         * no token, no provider script on the page, and the server skips the
         * check because nothing is configured. The builder preview passes
         * nothing here, so it never draws a challenge either.
         */
        captcha = null,
        /*
         * The browser's passive report on itself — screen, timezone, language.
         * Mirrored into hidden `client[…]` inputs so the SERVER sees the same
         * values on submit that the beacon sent, which is what keeps the device
         * fingerprint and the technical columns agreeing across both paths.
         *
         * Empty in the builder preview, where there is nothing to post to.
         */
        client = {},
        onsubmit = null,
        oninteract = null,
        onstep = null,
    } = $props();

    /**
     * The visible controls carry no `name`: they are driven by Svelte state, and
     * only the current page is even mounted — a native submit would silently
     * drop every answer on the pages the visitor is not looking at.
     *
     * So the whole answer set is mirrored into hidden inputs here instead. One
     * place, every element type, and it keeps working when a page is unmounted.
     */
    /** The live answer set. */
    let answers = $state({});

    const posted = $derived.by(() => {
        const out = [];

        for (const [key, value] of Object.entries(answers)) {
            if (Array.isArray(value)) {
                // An empty array must still post something, or the key vanishes
                // and a `required` array reads as absent rather than empty.
                if (value.length === 0) {
                    out.push({ name: `fields[${key}]`, value: '' });
                    continue;
                }

                for (const item of value) {
                    out.push({ name: `fields[${key}][]`, value: scalar(item) });
                }

                continue;
            }

            if (typeof value === 'boolean') {
                out.push({ name: `fields[${key}]`, value: value ? '1' : '0' });
                continue;
            }

            out.push({ name: `fields[${key}]`, value: scalar(value) });
        }

        return out;
    });

    /** A file answer is {id, name}; everything else is already a scalar. */
    function scalar(value) {
        if (value !== null && typeof value === 'object') return value.id ?? '';

        return value ?? '';
    }

    const activeLocale = $derived(locale ?? schema?.locale ?? 'en');
    const fallbackLocale = $derived(schema?.default_locale ?? 'en');
    /*
     * The schema's own flag wins; a locale-based fallback catches the case where
     * it is absent, which would otherwise render Arabic left-to-right with no
     * error anywhere.
     */
    const direction = $derived(
        directionFor(activeLocale, schema?.directions?.[activeLocale] ?? schema?.is_rtl ?? null),
    );

    const pages = $derived(schema?.pages ?? []);

    /*
     * The visited stack. Its last entry is the current page; everything before
     * it is where "back" goes. Plain page ids, so a pushState of this array is
     * a handful of strings.
     */
    let stack = $state([]);

    const currentId = $derived(stack.length ? stack[stack.length - 1] : (pages[0]?.id ?? null));
    const current = $derived(pages.find((p) => p.id === currentId) ?? pages[0] ?? null);
    const currentIndex = $derived(pages.findIndex((p) => p.id === current?.id));

    // Interstitials are entered from a button and returned from, so they are not
    // steps and never appear in the progress.
    const linearPages = $derived(pages.filter((p) => !p.is_interstitial));
    const linearIndex = $derived(linearPages.findIndex((p) => p.id === current?.id));
    const isLastLinear = $derived(linearIndex >= 0 && linearIndex === linearPages.length - 1);
    const canGoBack = $derived(stack.length > 1);

    /**
     * Whether the page the visitor is looking at is the one that can submit.
     *
     * The captcha hangs off this. A challenge drawn on page 1 of a three-page
     * form is solved minutes before it is posted, and a v2 token dies two minutes
     * after it is issued — so the visitor solves it, fills the rest of the form,
     * and submits a token the server has to reject.
     *
     * Mirrors the two ways a page can submit: an author-placed button that says
     * so, or — when the page carries no buttons of its own — the fallback Submit
     * rendered at the end of the linear run. ButtonControl treats any other
     * action, and a missing one, as `next`.
     */
    const submitsHere = $derived.by(() => {
        const buttons = (current?.fields ?? []).filter((f) => f.type === 'button');

        if (buttons.length) return buttons.some((f) => f?.settings?.action === 'submit');

        return isLastLinear;
    });

    const title = $derived(translate(schema?.title, activeLocale, fallbackLocale));
    const description = $derived(translate(schema?.description, activeLocale, fallbackLocale));
    const content = $derived(translate(schema?.content, activeLocale, fallbackLocale));

    /** Seed the stack, and re-seed when the form itself changes. */
    // Plain, not $state: tracking it would re-run the effect that writes it.
    let lastSchemaId = null;
    $effect(() => {
        const id = schema?.id ?? null;
        if (id === lastSchemaId) return;
        lastSchemaId = id;
        stack = pages.length ? [pages[0].id] : [];
    });

    /**
     * Every answer key needs a value before a control binds to it — binding to
     * undefined throws props_invalid_value, and an array control needs an array
     * rather than ''. Runs on the schema, so adding a field in the builder seeds
     * it immediately.
     */
    $effect(() => {
        for (const page of pages) {
            for (const field of page.fields ?? []) {
                // Headings, paragraphs and buttons hold no answer — seeding them
                // would post empty keys the server has no rule for.
                if (!ELEMENTS[field.type]?.input) continue;

                if (answers[field.key] === undefined) {
                    // Old input first (a rejected submission must not make the
                    // visitor retype everything), then the field's own default —
                    // resolved for this locale by FormsController::schema(), and
                    // null rather than '' when there is none, so the type's own
                    // empty value (an array, a false) still wins for the controls
                    // that need one. The builder's preview schema carries no
                    // resolved default, so a preview simply starts blank.
                    answers[field.key] = values?.[field.key]
                        ?? field.value_resolved
                        ?? emptyValue(field.type, field);
                }
            }
        }
    });

    function goTo(pageId, { replace = false } = {}) {
        if (!pageId || !pages.some((p) => p.id === pageId)) return;

        stack = replace ? [...stack.slice(0, -1), pageId] : [...stack, pageId];
        onstep?.({ pageId, stack: [...stack], direction: 'forward' });
    }

    function back() {
        // Pop or clamp, NEVER push: pushing would make canGoBack permanently
        // true and repeated backs would oscillate between two pages.
        if (stack.length <= 1) return;

        stack = stack.slice(0, -1);
        onstep?.({ pageId: stack[stack.length - 1], stack: [...stack], direction: 'back' });
    }

    function next() {
        const from = linearIndex >= 0 ? linearIndex : -1;
        const target = linearPages[from + 1];

        if (target) goTo(target.id);
    }

    function handleNavigate(action, field) {
        if (mode === 'preview') {
            // The preview still navigates — that is most of what it is for —
            // but it never submits.
            if (action === 'submit') return;
        }

        if (action === 'next') return next();
        if (action === 'back') return back();
        if (action === 'goto') return goTo(field?.target_form_page_id);
        if (action === 'submit') return submit();
    }

    function handleChange(field, value) {
        answers[field.key] = value;
        oninteract?.({ type: 'change', field, value });
    }


    /**
     * Send one file and return its media row.
     *
     * Lives here rather than in the control because the endpoint differs per
     * context — the public page has one, the builder preview has none — and a
     * leaf component must not know about either. Null uploadAction means the
     * control says "uploads are disabled in preview" instead of failing.
     */
    async function upload(field, file) {
        if (!uploadAction) throw new Error('Uploads are not available here.');

        const body = new FormData();
        body.append('file', file);
        body.append('field', field.key);
        body.append('submission_token', token ?? '');
        if (csrf) body.append('_token', csrf);

        const response = await fetch(uploadAction, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(response.status === 429
                ? 'Too many files. Please remove one first.'
                : 'That file could not be uploaded.');
        }

        return response.json();
    }

    let formEl = $state(null);

    /*
     * CAPTCHA.
     *
     * THE TOKEN FIELD IS `captcha_token`, which is what FormsController reads.
     * reCAPTCHA's own field is `g-recaptcha-response`: v2 posts it natively from
     * the textarea the widget injects, but v3 renders no widget and therefore no
     * field at all. So the hidden input below is the one name that covers both
     * versions, and the server accepts either — a token that only ever arrived
     * under Google's name still verifies.
     */
    let captchaToken = $state('');
    let captchaEl = $state(null);
    let captchaNotice = $state('');
    let captchaPending = $state(false);
    /*
     * The div the v2 widget was drawn into, so it can be drawn again when that
     * div is a different one — the block now only exists on the submitting page,
     * so going Back and forward destroys it and mounts a fresh one. A boolean
     * "already rendered" flag would leave the second visit with an empty box and
     * no way to solve the challenge.
     *
     * Plain, not $state: the effect that renders the widget writes it, and
     * tracking it would make that effect re-run itself.
     */
    let renderedInto = null;

    const captchaOn = $derived(mode === 'live' && !!captcha?.siteKey);
    const captchaV3 = $derived((captcha?.version ?? 'v2') === 'v3');

    /** Resolve once `check()` is truthy; reject if it never becomes so. */
    function poll(check, timeoutMs = 15000) {
        return new Promise((resolve, reject) => {
            const started = Date.now();

            (function tryNow() {
                if (check()) return resolve();
                if (Date.now() - started > timeoutMs) return reject(new Error('captcha script did not load'));

                setTimeout(tryNow, 100);
            })();
        });
    }

    /**
     * Wait for the provider script.
     *
     * Polled rather than hung off the script tag's `onload` parameter: that
     * needs a global callback to already exist when the async script executes,
     * and this island mounts on DOMContentLoaded — a race that loses silently.
     * `grecaptcha.ready` is v3's initialisation gate and is not guaranteed on
     * every v2 bundle, so it is used when present and skipped when not.
     */
    function providerReady() {
        return poll(() => typeof window.grecaptcha?.render === 'function'
            || typeof window.grecaptcha?.execute === 'function')
            .then(() => new Promise((resolve) => {
                if (typeof window.grecaptcha.ready === 'function') window.grecaptcha.ready(resolve);
                else resolve();
            }));
    }

    /** Draw the v2 checkbox, once per div, into the div inside the form. */
    $effect(() => {
        if (!captchaOn || captchaV3 || !captchaEl || captchaEl === renderedInto) return;

        renderedInto = captchaEl;

        // A freshly mounted widget is unsolved. Keeping the previous token would
        // post one the visitor can no longer see, and it may already be expired.
        captchaToken = '';
        captchaNotice = '';

        const target = captchaEl;
        const siteKey = captcha.siteKey;

        providerReady()
            .then(() => {
                // The visitor may have navigated away while the script loaded;
                // rendering into a detached node throws and would surface as a
                // failure notice on a form that is working perfectly well.
                if (captchaEl !== target) return;

                window.grecaptcha.render(target, {
                    sitekey: siteKey,
                    callback: (value) => {
                        captchaToken = value ?? '';
                        captchaNotice = '';
                    },
                    // A solved challenge goes stale after two minutes. Clearing
                    // the token lets the server reject it with its own message
                    // rather than the visitor being told the answer was wrong.
                    'expired-callback': () => {
                        captchaToken = '';
                    },
                    'error-callback': () => {
                        captchaToken = '';
                    },
                });
            })
            .catch(() => {
                captchaNotice = 'The verification could not be loaded. Please reload the page and try again.';
            });
    });

    /**
     * Mint a v3 token and submit with it.
     *
     * At submit time rather than on mount, because the token expires two
     * minutes after it is issued and a visitor reading a long form would post a
     * dead one.
     */
    async function withV3Token() {
        if (captchaPending) return;

        captchaPending = true;
        captchaNotice = '';

        try {
            await providerReady();
            captchaToken = await window.grecaptcha.execute(captcha.siteKey, { action: 'submit' });
        } catch {
            captchaNotice = 'The verification could not be completed. Please try again in a moment.';
            captchaPending = false;

            return;
        }

        captchaPending = false;

        // Svelte writes the hidden input on the next flush; submitting before
        // that posts the previous — empty — value.
        await tick();
        formEl?.requestSubmit();
    }

    function submit(event = null) {
        // Preview navigates but never sends.
        if (mode === 'preview') {
            event?.preventDefault();

            return;
        }

        // A caller that wants to handle submission itself gets to; otherwise the
        // native POST goes through untouched, which is what keeps Laravel's
        // withErrors / old-input round-trip working.
        if (onsubmit) {
            event?.preventDefault();
            onsubmit({ values: { ...answers }, pageId: current?.id, stack: [...stack] });

            return;
        }

        /*
         * v3 has no widget to solve, so the token is fetched here and the
         * submit is replayed once it lands.
         *
         * An UNSOLVED v2 checkbox is deliberately NOT blocked here: the post
         * goes through with an empty token and step 9 rejects it with the
         * provider's own translated message. Refusing client-side would make
         * the form unsubmittable for anyone the script failed to reach, and
         * would put the rule in two places.
         */
        if (captchaOn && captchaV3 && !captchaToken) {
            event?.preventDefault();
            withV3Token();

            return;
        }

        /*
         * Announced exactly once per genuine POST, and only on the branch that
         * actually lets one through: a button-triggered submit arrives here with
         * no event, calls requestSubmit below, and comes straight back with one.
         * Emitting on both would count every submission twice — and the v3
         * replay above returns before this line, so its retry is the only one
         * that counts.
         *
         * Telemetry hangs its final, synchronous beacon off this, so it must
         * stay ahead of the navigation the native submit is about to cause.
         */
        if (event) oninteract?.({ type: 'submit' });

        // Called from a submit-action button rather than a real submit event:
        // requestSubmit so the browser runs the normal submit path.
        if (!event) formEl?.requestSubmit();
    }
</script>

<div class="sisf" dir={direction} lang={activeLocale} data-sisf-mode={mode}>
    <!--
        THE form. The page around it must not wrap this in another one: nested
        forms are invalid HTML and the browser drops the inner one, which would
        take the hidden answers below with it.
    -->
    <form
        bind:this={formEl}
        class="sisf-form"
        method={mode === 'live' && action ? 'POST' : 'dialog'}
        action={mode === 'live' ? action : undefined}
        onsubmit={submit}
        novalidate
    >
        {#if mode === 'live'}
            {#if csrf}<input type="hidden" name="_token" value={csrf} />{/if}
            {#if token}<input type="hidden" name="submission_token" value={token} />{/if}

            {#if honeypot}
                <!--
                    Off-screen with inline styles, not a class: a bot that reads
                    the stylesheet can learn which class means hidden, and one
                    that ignores CSS entirely would see a visible field and fill
                    it in — which is the outcome we want.

                    It posts even when untouched, so the server can tell "a human
                    left it empty" from "a bot stripped the field".
                -->
                <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
                    <input type="text" name="honeypot[{honeypot}]" value="" tabindex="-1" autocomplete="off" />
                </div>
            {/if}

            {#each Object.entries(client) as [name, value] (name)}
                <input type="hidden" name="client[{name}]" value={value ?? ''} />
            {/each}

            {#each posted as entry (entry.name + entry.value)}
                <input type="hidden" name={entry.name} value={entry.value} />
            {/each}
        {/if}

        {#if title}<h1 class="sisf-title">{title}</h1>{/if}
        {#if description}<p class="sisf-description">{description}</p>{/if}
        <!-- Admin-authored, same trust level as Page::content. A visitor can
             never write into it. -->
        {#if content}<div class="sisf-content">{@html content}</div>{/if}

        {#if linearPages.length > 1 && linearIndex >= 0}
            <p class="sisf-progress" aria-live="polite">
                Step {linearIndex + 1} of {linearPages.length}
            </p>
        {/if}

        {#if current}
            <FormPage
                page={current}
                values={answers}
                {errors}
                locale={activeLocale}
                {fallbackLocale}
                disabled={submitting}
                onchange={handleChange}
                onfocus={(field) => oninteract?.({ type: 'focus', field })}
                onblur={(field) => oninteract?.({ type: 'blur', field })}
                onnavigate={handleNavigate}
                onupload={upload}
            />
        {/if}

        {#if captchaOn && submitsHere}
            <!--
                Inside the form on purpose. v2 injects its own textarea named
                `g-recaptcha-response` into the div below, and it only posts if
                it is within this element — but the hidden input is what the
                server reads, because v3 injects nothing.

                And on the SUBMITTING page only — see submitsHere. The token has
                a two-minute life, so a challenge drawn on page 1 of a multi-page
                form is dead by the time that form is posted.
            -->
            <div class="sisf-captcha">
                {#if !captchaV3}<div bind:this={captchaEl}></div>{/if}

                <!-- One-way, never bound: the value only ever travels outward,
                     and a binding on a hidden input has no event to read back. -->
                <input type="hidden" name="captcha_token" value={captchaToken} />

                {#if captchaNotice}<p class="sisf-error" role="alert">{captchaNotice}</p>{/if}
            </div>
        {/if}

        <!--
            Fallback controls. A form whose author placed no button still has to
            be completable, so these appear only when the current page has none
            of its own.
        -->
        {#if !(current?.fields ?? []).some((f) => f.type === 'button')}
            <div class="sisf-actions">
                {#if canGoBack}
                    <button type="button" class="sisf-btn sisf-btn--secondary" onclick={back}>Back</button>
                {/if}
                {#if isLastLinear}
                    <button type="submit" class="sisf-btn sisf-btn--primary" disabled={submitting || captchaPending}>
                        {submitting || captchaPending ? 'Sending…' : 'Submit'}
                    </button>
                {:else}
                    <button type="button" class="sisf-btn sisf-btn--primary" onclick={next}>Next</button>
                {/if}
            </div>
        {/if}
    </form>
</div>
