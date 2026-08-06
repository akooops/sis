<script>
    /**
     * A numeric answer.
     *
     * The raw string is passed up, not a parsed number: an empty box must stay
     * empty rather than becoming 0, and mid-typing states like "-" or "1." are
     * not numbers yet. The server casts on the way into storage.
     */
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
    const step = $derived(field?.settings?.step ?? (field?.validation?.integer_only ? 1 : 'any'));
</script>

<input
    {id}
    type="number"
    class="sisf-input"
    value={value ?? ''}
    {placeholder}
    {disabled}
    {step}
    min={field?.validation?.min ?? undefined}
    max={field?.validation?.max ?? undefined}
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={(e) => onchange?.(field, e.currentTarget.value)}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
/>
