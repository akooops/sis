<script>
    /** A multi-line text answer. */
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
    const rows = $derived(Number(field?.settings?.rows) > 0 ? Number(field.settings.rows) : 4);
</script>

<textarea
    {id}
    class="sisf-textarea"
    {rows}
    {placeholder}
    {disabled}
    maxlength={field?.validation?.max_length ?? undefined}
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={(e) => onchange?.(field, e.currentTarget.value)}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
>{value ?? ''}</textarea>
