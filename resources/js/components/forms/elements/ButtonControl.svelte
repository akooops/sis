<script>
    /**
     * A navigation control.
     *
     * type="button" for everything including submit: the renderer decides what
     * submitting means, and a native submit button would fire the form even in
     * preview, where nothing should be sent.
     */
    import { translate } from '@/lib/forms/i18n';

    let {
        field,
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        id = undefined,
        onnavigate = null,
    } = $props();

    const ACTIONS = ['next', 'back', 'goto', 'submit'];
    const action = $derived(ACTIONS.includes(field?.settings?.action) ? field.settings.action : 'next');

    const label = $derived(
        translate(field?.label, locale, fallbackLocale)
            || { next: 'Next', back: 'Back', goto: 'Continue', submit: 'Submit' }[action],
    );

    const variant = $derived(field?.settings?.variant === 'secondary' ? 'secondary' : 'primary');
</script>

<button
    {id}
    type="button"
    class="sisf-btn sisf-btn--{variant}"
    {disabled}
    data-sisf-action={action}
    onclick={() => onnavigate?.(action, field)}
>
    {label}
</button>
