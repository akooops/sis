<script>
    /**
     * The container contract — THE ONLY FILE THAT EMITS IT.
     *
     * Every element renders the same wrapper, and the admin's css_id / css_class
     * land on that wrapper rather than on the control. That is what makes
     * `.my-class input`, `.my-class .sisf-label` and `.my-class.is-invalid input`
     * all work, for every element type, without the admin knowing what markup a
     * given type produces.
     *
     * Controls never render their own label or error. If a second file starts
     * emitting `sisf-el`, the contract stops being a contract.
     *
     * ONE type opts out entirely — see `bare` below. A hidden field shows the
     * visitor nothing, so it gets no container at all: an empty `.sisf-el` would
     * still take a slot of the page's flex gap and leave a mystery hole in the
     * form.
     *
     * Imports nothing from Inertia, Ziggy or the API client — that is what lets
     * the same component run inside the admin app and inside the standalone
     * public island.
     */
    import { ELEMENTS } from '@site/lib/forms/elements';
    import { translate } from '@site/lib/forms/i18n';

    let {
        field,
        value = null,
        error = null,
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        // Forwarded, not read: this file owns the label and the error, and a
        // control only needs these when it has copy of its OWN — which is
        // FileControl, and GroupControl for its Add/Remove wording.
        labels = {},
        /**
         * The WHOLE error map, forwarded for the same reason as `labels`: a
         * repeatable group has to look up its children's messages itself, keyed
         * `group.index.child`, and only it knows those paths.
         */
        errors = {},
        /**
         * The answer path this element occupies, when it is not simply its own
         * key — `education.0.institution` for a child inside a repeat.
         *
         * Drives `data-sisf-key`, which is what the renderer scrolls to when the
         * server rejects a field: without it every repeat of a child would carry
         * the same marker and the visitor would be sent to the first row no
         * matter which one was actually wrong.
         */
        keyPath = null,
        /**
         * Which repeat this element is in, or null outside a group. Only used to
         * keep DOM ids unique — the same child rendered three times would
         * otherwise emit `sisf-<ulid>` three times, and every `<label for>` in
         * the group would point at the first row's input.
         */
        instance = null,
        onchange = null,
        onfocus = null,
        onblur = null,
        onnavigate = null,
        onupload = null,
    } = $props();

    // Null when the type was dropped from the registry. Rendering nothing beats
    // throwing: the same guard FormField::element() applies server-side, for the
    // same reason — an unknown code must not take a public page down.
    const spec = $derived(ELEMENTS[field?.type] ?? null);

    const controlId = $derived(instance === null ? `sisf-${field?.id}` : `sisf-${field?.id}-${instance}`);
    const errorId = $derived(`${controlId}-error`);

    /** What the renderer's error-scroll looks for. */
    const answerKey = $derived(keyPath ?? field?.key);

    /*
     * The admin's css_id, suffixed inside a repeat. A child rendered three times
     * would otherwise emit the same id three times — invalid HTML, and every
     * `#my-id` rule in the form's stylesheet would only ever match the first row.
     */
    const wrapperId = $derived(
        !field?.css_id ? undefined : instance === null ? field.css_id : `${field.css_id}-${instance}`,
    );

    const label = $derived(translate(field?.label, locale, fallbackLocale));

    const filled = $derived(
        value !== null && value !== undefined && value !== '' && !(Array.isArray(value) && value.length === 0),
    );

    const describedBy = $derived(error ? errorId : undefined);

    /**
     * How much of the line this field takes on a wide screen.
     *
     * Emitted as a class rather than an inline style so the form's own stylesheet
     * and site/forms-theme.css can still override it — an inline width would beat
     * both. `100` renders no class at all, because full width is the default the
     * CSS already gives every child.
     */
    const widthClass = $derived.by(() => {
        const width = String(field?.settings?.width ?? '100');

        return width === '100' ? '' : `sisf-el--w-${width}`;
    });

    const classes = $derived(
        [
            'sisf-el',
            `sisf-el--${field?.type}`,
            widthClass,
            field?.is_required ? 'is-required' : '',
            error ? 'is-invalid' : '',
            filled ? 'is-filled' : '',
            field?.css_class ?? '',
        ]
            .filter(Boolean)
            .join(' '),
    );
</script>

{#if spec}
    {@const Control = spec.component}

    <!--
        Two shapes, one contract. A group of inputs is a <fieldset> with a
        <legend>, because a <label for> pointing at a group has no single control
        to point at and screen readers ignore it. Everything else is a <div> with
        a real <label for>.

        And one shape that is no shape: a `bare` element emits its control and
        nothing else — no wrapper, no label, no error — because it renders
        nothing the visitor can see or be told about.
    -->
    {#if spec.bare}
        <!-- No locale props: there is no placeholder to resolve and no label to
             translate. The value arrives already resolved, like every other. -->
        <Control {field} {value} id={controlId} />
    {:else if spec.group}
        <fieldset
            class={classes}
            id={wrapperId}
            data-sisf-type={field.type}
            data-sisf-key={answerKey}
        >
            {#if label}
                <legend class="sisf-label">
                    {label}{#if field.is_required}<span class="sisf-required" aria-hidden="true">*</span>{/if}
                </legend>
            {/if}

            <div class="sisf-control">
                <Control
                    {field}
                    {value}
                    {locale}
                    {fallbackLocale}
                    {disabled}
                    {labels}
                    {errors}
                    id={controlId}
                    invalid={!!error}
                    {describedBy}
                    {onchange}
                    {onfocus}
                    {onblur}
                    {onnavigate}
                    {onupload}
                />
            </div>

            {#if error}<p class="sisf-error" id={errorId} role="alert">{error}</p>{/if}
        </fieldset>
    {:else}
        <div
            class={classes}
            id={wrapperId}
            data-sisf-type={field.type}
            data-sisf-key={answerKey}
        >
            {#if spec.labelled && label}
                <label class="sisf-label" for={controlId}>
                    {label}{#if field.is_required}<span class="sisf-required" aria-hidden="true">*</span>{/if}
                </label>
            {/if}

            <div class="sisf-control">
                <Control
                    {field}
                    {value}
                    {locale}
                    {fallbackLocale}
                    {disabled}
                    {labels}
                    {errors}
                    id={controlId}
                    invalid={!!error}
                    {describedBy}
                    {onchange}
                    {onfocus}
                    {onblur}
                    {onnavigate}
                    {onupload}
                />
            </div>

            {#if error}<p class="sisf-error" id={errorId} role="alert">{error}</p>{/if}
        </div>
    {/if}
{/if}
