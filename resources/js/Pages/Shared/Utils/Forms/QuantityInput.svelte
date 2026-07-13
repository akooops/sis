<!-- QuantityInput.svelte -->
<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let value = '';
    export let label = 'Quantity';
    export let placeholder = 'Enter quantity';
    export let required = false;
    export let disabled = false;
    export let error = null;
    export let is_integer = false;
    export let unit = '';
    export let id = 'quantity-input';
    export let maxDecimals = 3;

    $: step = is_integer ? 1 : Math.pow(10, -maxDecimals);

    function normalizeValue(raw) {
        if (raw === '' || raw === null || raw === undefined) {
            return '';
        }

        let str = String(raw);

        if (is_integer) {
            const intValue = parseInt(str.split('.')[0], 10);
            return Number.isNaN(intValue) ? '' : String(Math.max(0, intValue));
        }

        const num = parseFloat(str);
        if (Number.isNaN(num)) {
            return '';
        }

        const factor = Math.pow(10, maxDecimals);
        const rounded = Math.round(Math.max(0, num) * factor) / factor;
        return String(rounded);
    }

    function handleInput(event) {
        const raw = event.target.value;

        if (raw === '') {
            value = '';
            dispatch('input', { value: '' });
            return;
        }

        const normalized = normalizeValue(raw);

        if (normalized !== raw) {
            event.target.value = normalized;
        }

        value = normalized;
        dispatch('input', { value });
    }

    function handleBlur(event) {
        if (!event.target.value) {
            return;
        }

        const normalized = normalizeValue(event.target.value);

        if (normalized !== value) {
            event.target.value = normalized;
            value = normalized;
            dispatch('input', { value });
        }
    }

    $: if (value !== '' && value !== null && value !== undefined) {
        const normalized = normalizeValue(value);
        if (normalized !== String(value)) {
            value = normalized;
            dispatch('input', { value });
        }
    }
</script>

<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-mono" for={id}>
        {label} {#if required}<span class="text-destructive">*</span>{/if}
    </label>

    <div class="flex items-center gap-2">
        <input
            {id}
            type="number"
            min="0"
            {step}
            class="kt-input flex-1 {error ? 'kt-input-error' : ''}"
            {placeholder}
            {disabled}
            {value}
            on:input={handleInput}
            on:blur={handleBlur}
        />
        {#if unit}
            <span class="text-sm text-muted-foreground shrink-0">{unit}</span>
        {/if}
    </div>

    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
