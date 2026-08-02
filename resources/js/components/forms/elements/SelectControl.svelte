<script>
    /**
     * A dropdown. Option labels are translated; option VALUES never are, so the
     * same answer reads identically whatever language it was given in.
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

    const multiple = $derived(!!field?.settings?.is_multiple);
    const options = $derived(field?.options ?? []);
    const placeholder = $derived(translate(field?.placeholder, locale, fallbackLocale));

    const selected = $derived(multiple ? (Array.isArray(value) ? value : []) : (value ?? ''));

    function emit(event) {
        const el = event.currentTarget;

        onchange?.(
            field,
            multiple ? Array.from(el.selectedOptions).map((o) => o.value) : el.value,
        );
    }
</script>

<select
    {id}
    class="sisf-select"
    {multiple}
    {disabled}
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    onchange={emit}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
>
    {#if !multiple}
        <option value="" selected={!selected}>{placeholder || '—'}</option>
    {/if}

    {#each options as option (option.value)}
        <option
            value={option.value}
            selected={multiple ? selected.includes(option.value) : selected === option.value}
        >
            {translate(option.label, locale, fallbackLocale) || option.value}
        </option>
    {/each}
</select>
