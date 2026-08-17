<script>
    /**
     * A rule across the form, optionally with a caption sitting on it.
     *
     * The caption is plain text, not markup — a separator is a break, and letting
     * an admin put a paragraph on one turns it into a content block that happens
     * to have a line through it.
     */
    import { translate } from '@site/lib/forms/i18n';

    let { field, locale = 'en', fallbackLocale = 'en' } = $props();

    const caption = $derived((translate(field?.content, locale, fallbackLocale) ?? '').trim());

    const spacing = $derived(field?.settings?.spacing ?? 'normal');
</script>

<!-- role="presentation" when there is no caption: a bare rule is decoration and
     announcing it interrupts the form for no information. With a caption it is a
     real <hr> carrying text, so it keeps its default separator semantics. -->
<div class="sisf-separator sisf-separator--{spacing}" role={caption ? undefined : 'presentation'}>
    {#if caption}
        <span class="sisf-separator-text">{caption}</span>
    {/if}
</div>
