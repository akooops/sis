<script>
    /**
     * Switch — a Metronic `kt-switch` checkbox, two-way bound via `value`.
     * Used for the provider enable toggle and boolean schema fields.
     *   <Switch bind:value={enabled} onchange={(v) => …} />
     */
    let {
        value = $bindable(false),
        disabled = false,
        id = undefined,
        onchange,
        ...rest
    } = $props();

    function handle(event) {
        value = event.currentTarget.checked;
        onchange?.(value);
    }
</script>

<input
    {id}
    type="checkbox"
    class="kt-switch"
    checked={value}
    {disabled}
    onchange={handle}
    {...rest}
/>
