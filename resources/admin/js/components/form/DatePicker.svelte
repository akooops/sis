<script>
    /**
     * DatePicker — flatpickr wrapper (no jQuery). Svelte 5 runes.
     *
     *   <DatePicker bind:value={form.data.due_at} />                 // single date
     *   <DatePicker bind:value={form.data.due_at} enableTime />      // date + time
     *   <DatePicker mode="range" bind:value={range} />              // range -> [from, to]
     *
     * Emits ISO date strings ('Y-m-d', or full ISO when enableTime). Range mode
     * binds an array [from, to]. The calendar follows the document `dir` for RTL.
     */
    import flatpickr from 'flatpickr';
    import 'flatpickr/dist/flatpickr.css';
    import { onDestroy } from 'svelte';

    let {
        value = $bindable(null),
        mode = 'single', // 'single' | 'range' | 'multiple'
        enableTime = false,
        placeholder = null,
        disabled = false,
        invalid = false,
        minDate = null,
        maxDate = null,
        onchange,
    } = $props();

    let input;
    let fp;

    const dateFormat = $derived(enableTime ? 'Y-m-d H:i' : 'Y-m-d');

    function currentValue() {
        // flatpickr accepts arrays for range/multiple; scalars otherwise.
        return value ?? (mode === 'range' || mode === 'multiple' ? [] : null);
    }

    $effect(() => {
        fp = flatpickr(input, {
            mode,
            enableTime,
            dateFormat,
            allowInput: true,
            defaultDate: currentValue(),
            minDate,
            maxDate,
            time_24hr: true,
            onChange: (selectedDates, dateStr) => {
                let next;
                if (mode === 'range' || mode === 'multiple') {
                    next = selectedDates.map((d) => fp.formatDate(d, dateFormat));
                } else {
                    next = dateStr || null;
                }
                value = next;
                onchange?.(next);
            },
        });

        return () => fp?.destroy();
    });

    // Reflect external value changes (e.g. form reset) back into flatpickr.
    $effect(() => {
        if (fp && value !== undefined) {
            fp.setDate(currentValue(), false);
        }
    });

    onDestroy(() => fp?.destroy());
</script>

<input
    bind:this={input}
    type="text"
    class="kt-input {invalid ? 'border-destructive' : ''}"
    placeholder={placeholder ?? (mode === 'range' ? 'Select range' : 'Select date')}
    {disabled}
    aria-invalid={invalid}
/>
