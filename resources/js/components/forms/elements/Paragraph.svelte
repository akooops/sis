<script>
    /**
     * A block of explanatory copy. Display only.
     *
     * Escaped, not {@html}: this is the plain-text element. An admin who wants
     * markup uses the rich content element, which is where that trust is
     * documented.
     */
    import { translate } from '@/lib/forms/i18n';

    let { field, locale = 'en', fallbackLocale = 'en' } = $props();

    const text = $derived(translate(field?.content, locale, fallbackLocale));
</script>

{#if text}
    <p class="sisf-paragraph">{text}</p>
{/if}
