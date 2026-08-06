<script>
    /**
     * ContentLinkInput — where a record points: nowhere, an external URL, or any
     * internal record with a public slug.
     *
     *   <ContentLinkInput bind:type={form.data.linkable_type} bind:id={form.data.linkable_id}
     *       bind:url={form.data.url} initialOption={{ value, label }} />
     *
     * `type` is null (no link), 'url', or a MorphType alias. The two sides are
     * mutually exclusive server-side, so changing the kind drops the one being
     * left behind — a leftover value is a 422, not a silently ignored field.
     */
    import Select from '@/components/form/Select.svelte';
    import Input from '@/components/form/Input.svelte';
    import { LINKABLE_OPTIONS, LINKABLE_RESOURCES } from '@/lib/linkable';

    let {
        type = $bindable(null),
        url = $bindable(''),
        id = $bindable(null),
        initialOption = null,
        invalid = false,
        disabled = false,
    } = $props();

    const resource = $derived(type && type !== 'url' ? LINKABLE_RESOURCES[type] : null);

    // value= plus onchange, not bind:, so the outgoing kind is still readable here.
    function chooseKind(next) {
        if (next === type) return;
        // An id only means anything under the type it was picked in.
        id = null;
        if (next !== 'url') url = '';
        type = next;
    }
</script>

<div class="flex flex-col gap-2">
    <Select options={LINKABLE_OPTIONS} value={type} onchange={chooseKind} placeholder="No link" {disabled} />

    {#if type === 'url'}
        <Input type="url" bind:value={url} placeholder="https://…" {invalid} {disabled} />
    {:else if resource}
        <!-- Select resolves a preselected id it never loaded via filter[id];
             initialOption only makes the label appear without that round trip. -->
        <Select
            resource={resource.route}
            labelKey={resource.labelKey}
            bind:value={id}
            placeholder="Search {resource.label.toLowerCase()}s…"
            initialOptions={initialOption ? [initialOption] : []}
            {invalid}
            {disabled}
        />
    {/if}
</div>
