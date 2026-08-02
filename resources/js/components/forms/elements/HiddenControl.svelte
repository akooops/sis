<script>
    /**
     * An answer the visitor never sees.
     *
     * The input carries NO `name`. FormRenderer mirrors the whole answer set
     * into its own `fields[…]` hidden inputs, so a name here would post the key
     * twice — once as an array, once as a scalar — and the server would read
     * whichever arrived last.
     *
     * So this is a marker: it puts the value in the DOM where the admin's own
     * stylesheet and any page script can find it by id, and the value it holds
     * is the one FormRenderer already seeded from the field's `value`.
     */
    let {
        field,
        value = '',
        id = undefined,
    } = $props();
</script>

<input {id} type="hidden" class="sisf-hidden" value={value ?? ''} data-sisf-key={field?.key} />
