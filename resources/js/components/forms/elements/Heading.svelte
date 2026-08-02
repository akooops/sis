<script>
    /** A section heading. Display only — Element.svelte owns the container. */
    import { translate } from '@/lib/forms/i18n';

    let { field, locale = 'en', fallbackLocale = 'en' } = $props();

    const text = $derived(translate(field?.content, locale, fallbackLocale));

    // Constrained to the six real heading tags: svelte:element will render
    // whatever string it is handed, and a settings blob is admin-editable.
    const LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    const level = $derived(LEVELS.includes(field?.settings?.level) ? field.settings.level : 'h2');
</script>

{#if text}
    <svelte:element this={level} class="sisf-heading">{text}</svelte:element>
{/if}
