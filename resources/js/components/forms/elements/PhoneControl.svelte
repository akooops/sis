<script>
    /**
     * A phone number.
     *
     * A plain tel input, deliberately NOT intl-tel-input: that library is ~90KB
     * plus its country metadata, and this runs on a public page that must stay
     * small. The number is normalised to E164 server-side by PhoneFormatter
     * before it is validated, so the visitor can type it however they like as
     * long as it carries a country code.
     */
    import { translate } from '@/lib/forms/i18n';

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

    const placeholder = $derived(translate(field?.placeholder, locale, fallbackLocale) || '+213555123456');
</script>

<input
    {id}
    type="tel"
    class="sisf-input"
    value={value ?? ''}
    {placeholder}
    {disabled}
    inputmode="tel"
    autocomplete="tel"
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={(e) => onchange?.(field, e.currentTarget.value)}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
/>
