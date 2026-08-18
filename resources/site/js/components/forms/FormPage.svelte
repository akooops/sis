<script>
    /**
     * One page of a form. Renders its own heading, then every element.
     *
     * Nothing is filtered out here: an element that shows the visitor nothing —
     * a hidden field — still renders, because it is Element.svelte that decides
     * what markup a type produces, and its answer still has to submit.
     */
    import Element from './Element.svelte';
    import { translate } from '@site/lib/forms/i18n';

    let {
        page,
        values = {},
        errors = {},
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        // The renderer's own chrome, already merged over its English defaults.
        // Only the controls that speak for themselves read it — see FileControl.
        labels = {},
        onchange = null,
        onfocus = null,
        onblur = null,
        onnavigate = null,
        onupload = null,
        // The challenge, when one is due on this page. A SNIPPET rather than a
        // flag, because the renderer owns the widget's state and its lifecycle —
        // this component owns only the question of WHERE it belongs.
        captcha = null,
    } = $props();

    const title = $derived(translate(page?.title, locale, fallbackLocale));

    const fields = $derived(page?.fields ?? []);

    /**
     * The page's elements, with RUNS OF ADJACENT BUTTONS COLLAPSED INTO ONE ROW.
     *
     * Every element is a block in the page's column flex, so Back and Next —
     * two separate button elements — stacked one above the other, each on its
     * own full-width line. A wizard's controls belong side by side.
     *
     * Grouped here rather than in CSS because CSS cannot see "these three
     * siblings are consecutive buttons": the page is a column, and anything that
     * made button elements inline would have to apply to all of them
     * individually, which is what already happens.
     *
     * ADJACENT ONLY, and author order is preserved inside the row. A button the
     * admin deliberately placed between two questions stays where they put it,
     * and a row reads left-to-right in the order the builder shows.
     *
     * @type {Array<{id: string, buttons: boolean, fields: Array<object>}>}
     */
    const groups = $derived.by(() => {
        const out = [];

        for (const field of fields) {
            const isButton = field?.type === 'button';
            const last = out[out.length - 1];

            if (isButton && last?.buttons) {
                last.fields.push(field);

                continue;
            }

            out.push({ id: field.id, buttons: isButton, fields: [field] });
        }

        return out;
    });

    /**
     * The group the captcha must sit IMMEDIATELY BEFORE, or null for "the end".
     *
     * A challenge belongs directly above the control that submits. It used to be
     * rendered by FormRenderer after this whole component, which put it above the
     * renderer's OWN fallback button but BELOW one the form's author placed —
     * because an author's button is simply another element on the page.
     *
     * The submit row is the anchor, not the first or last row: an author may put
     * a button between two questions, and a challenge above THAT is just as
     * stranded as one below the submit. Falling back to the last button row keeps
     * something sensible on a page whose submit control is the renderer's own,
     * and null means this page has no buttons at all — the fallback row is
     * rendered after this section, so the end of the section is still above it.
     */
    const captchaBeforeId = $derived.by(() => {
        const rows = groups.filter((group) => group.buttons);

        if (rows.length === 0) {
            return null;
        }

        const submits = rows.find((row) => row.fields.some((f) => f?.settings?.action === 'submit'));

        return (submits ?? rows[rows.length - 1]).id;
    });
</script>

<!-- The admin's own handles, same pair every element carries: the form's
     stylesheet targets a page by id or class, and `data-sisf-page` (the ULID)
     stays as the class-free fallback. -->
<section class="sisf-page {page?.css_class ?? ''}" id={page?.css_id || undefined} data-sisf-page={page?.id}>
    {#if title}<h2 class="sisf-page-title">{title}</h2>{/if}

    {#each groups as group (group.id)}
        {#if captcha && group.id === captchaBeforeId}{@render captcha()}{/if}

        {#if group.buttons}
            <!-- Back and Next on one line. The wrapper is presentational only:
                 each button keeps its own .sisf-el, so the admin's css_id and
                 css_class still land where the container contract says. -->
            <div class="sisf-button-row">
                {#each group.fields as field (field.id)}
                    <Element
                        {field}
                        value={values[field.key]}
                        error={errors[field.key] ?? null}
                        {errors}
                        {locale}
                        {fallbackLocale}
                        {disabled}
                        {labels}
                        {onchange}
                        {onfocus}
                        {onblur}
                        {onnavigate}
                        {onupload}
                    />
                {/each}
            </div>
        {:else}
            {@const field = group.fields[0]}
            <Element
                {field}
                value={values[field.key]}
                error={errors[field.key] ?? null}
                {errors}
                {locale}
                {fallbackLocale}
                {disabled}
                {labels}
                {onchange}
                {onfocus}
                {onblur}
                {onnavigate}
                {onupload}
            />
        {/if}
    {/each}

    <!-- No buttons on this page, so there is nothing to sit above within it. The
         renderer's fallback row follows this section, so here IS above it. -->
    {#if captcha && captchaBeforeId === null}{@render captcha()}{/if}
</section>
