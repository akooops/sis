<script>
    /** An email address. */
    import { translate } from '@site/lib/forms/i18n';

    let {
        field,
        value = '',
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        id = undefined,
        invalid = false,
        describedBy = undefined,
        onchange = null,
        onfocus = null,
        onblur = null,
    } = $props();

    const placeholder = $derived(translate(field?.placeholder, locale, fallbackLocale));
</script>

<input
    {id}
    type="email"
    class="sisf-input"
    value={value ?? ''}
    {placeholder}
    {disabled}
    inputmode="email"
    autocomplete="email"
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={(e) => onchange?.(field, e.currentTarget.value)}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
/>
