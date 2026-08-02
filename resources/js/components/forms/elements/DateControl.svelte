<script>
    /**
     * A date, optionally with a time.
     *
     * Native date / datetime-local rather than flatpickr: the public page must
     * stay small, and the native control is already localised and keyboard
     * accessible. The value shape matches the server's date_format exactly —
     * `Y-m-d` or `Y-m-d H:i` — so the two never disagree about what was sent.
     * datetime-local emits `YYYY-MM-DDTHH:MM`, hence the T swap.
     */
    let {
        field,
        value = '',
        disabled = false,
        id = undefined,
        invalid = false,
        describedBy = undefined,
        onchange = null,
        onfocus = null,
        onblur = null,
    } = $props();

    const withTime = $derived(!!field?.settings?.include_time);

    const displayValue = $derived(withTime ? String(value ?? '').replace(' ', 'T') : (value ?? ''));

    function emit(raw) {
        onchange?.(field, withTime ? String(raw).replace('T', ' ') : raw);
    }
</script>

<input
    {id}
    type={withTime ? 'datetime-local' : 'date'}
    class="sisf-input"
    value={displayValue}
    {disabled}
    min={field?.validation?.min_date ?? undefined}
    max={field?.validation?.max_date ?? undefined}
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={(e) => emit(e.currentTarget.value)}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
/>
