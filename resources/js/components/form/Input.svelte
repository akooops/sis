<script>
    /**
     * Input — KTUI `kt-input` text field. Two-way bind via `value` ($bindable).
     * Uses an explicit oninput handler so `type` can stay dynamic (Svelte
     * disallows bind:value with a dynamic type).
     *   <Input type="email" bind:value={form.data.email} invalid={!!form.errors.email} />
     */
    let {
        value = $bindable(''),
        type = 'text',
        invalid = false,
        class: klass = '',
        oninput,
        ...rest
    } = $props();

    function handleInput(event) {
        value = event.currentTarget.value;
        oninput?.(event);
    }
</script>

<input
    {type}
    {value}
    class="kt-input {invalid ? 'kt-input-error border-destructive' : ''} {klass}"
    oninput={handleInput}
    aria-invalid={invalid}
    {...rest}
/>
