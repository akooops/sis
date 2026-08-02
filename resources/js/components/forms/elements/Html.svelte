<script>
    /**
     * Rich content authored in TinyMCE. Display only.
     *
     * Rendered unescaped, at the same trust level as Page::content: it is
     * admin-authored and a public visitor can never write into it. This is the
     * only element that does so, which is why it is its own type rather than a
     * setting on the paragraph.
     */
    import { translate } from '@/lib/forms/i18n';

    let { field, locale = 'en', fallbackLocale = 'en' } = $props();

    const html = $derived(translate(field?.content, locale, fallbackLocale));
</script>

{#if html}
    <div class="sisf-html">{@html html}</div>
{/if}
