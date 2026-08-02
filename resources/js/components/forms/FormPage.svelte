<script>
    /**
     * One page of a form. Renders its own heading, then every element.
     *
     * Nothing is filtered out here: an element that shows the visitor nothing —
     * a hidden field — still renders, because it is Element.svelte that decides
     * what markup a type produces, and its answer still has to submit.
     */
    import Element from './Element.svelte';
    import { translate } from '@/lib/forms/i18n';

    let {
        page,
        values = {},
        errors = {},
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        onchange = null,
        onfocus = null,
        onblur = null,
        onnavigate = null,
        onupload = null,
    } = $props();

    const title = $derived(translate(page?.title, locale, fallbackLocale));

    const fields = $derived(page?.fields ?? []);
</script>

<!-- The admin's own handles, same pair every element carries: the form's
     stylesheet targets a page by id or class, and `data-sisf-page` (the ULID)
     stays as the class-free fallback. -->
<section class="sisf-page {page?.css_class ?? ''}" id={page?.css_id || undefined} data-sisf-page={page?.id}>
    {#if title}<h2 class="sisf-page-title">{title}</h2>{/if}

    {#each fields as field (field.id)}
        <Element
            {field}
            value={values[field.key]}
            error={errors[field.key] ?? null}
            {locale}
            {fallbackLocale}
            {disabled}
            {onchange}
            {onfocus}
            {onblur}
            {onnavigate}
            {onupload}
        />
    {/each}
</section>
