<script>
    /**
     * Filters — one config-driven filter drawer for every index page. Pass a
     * field config; the component renders the controls and emits a flat values
     * object mapped to the query builder's `filter[...]` params.
     *
     *   <Filters
     *       bind:open={filtersOpen}
     *       config={[
     *           { key:'type', type:'select', label:'Type', options:[{value:'raw',label:'Raw'}] },
     *           { key:'is_active', type:'boolean', label:'Active' },
     *           { key:'created', type:'daterange', label:'Created' },   // -> created_from / created_to
     *           { key:'stock', type:'numberrange', label:'Stock' },     // -> stock_min / stock_max
     *           { key:'name', type:'text', label:'Name' },
     *       ]}
     *       values={currentFilters}
     *       onapply={(v) => list.setFilters(v)}
     *   />
     *
     * A resource-select can depend on another field in the same drawer: give any
     * of its props a function of the current draft, and name the field it
     * follows so a stale value can't outlive the choice that produced it.
     *
     *   { key:'causer_type', type:'select', options:[…] },
     *   { key:'causer_id', type:'resource-select', dependsOn:'causer_type',
     *     resource:(d) => CAUSER_RESOURCES[d.causer_type]?.route ?? null }
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';

    let { open = $bindable(false), config = [], values = {}, onapply } = $props();

    // Working copy; seeded from applied values whenever the drawer opens.
    let draft = $state({});

    $effect(() => {
        if (open) draft = { ...values };
    });

    function set(key, value) {
        const next = { ...draft, [key]: value };

        // Clear anything that followed this field: a causer_id picked for
        // "User" is meaningless once the type becomes "API key".
        for (const field of config) {
            if (field.dependsOn === key) next[field.key] = field.multiple ? [] : null;
        }

        draft = next;
    }

    // A resource-select prop may be a plain value or a function of the draft.
    function resolve(prop) {
        return typeof prop === 'function' ? prop(draft) : prop;
    }

    function fromKey(field) {
        return field.fromKey ?? `${field.key}_from`;
    }
    function toKey(field) {
        return field.toKey ?? `${field.key}_to`;
    }
    function minKey(field) {
        return field.minKey ?? `${field.key}_min`;
    }
    function maxKey(field) {
        return field.maxKey ?? `${field.key}_max`;
    }

    function apply() {
        // Drop empty values so the query stays clean.
        const out = {};
        for (const [k, v] of Object.entries(draft)) {
            if (v !== null && v !== undefined && v !== '') out[k] = v;
        }
        onapply?.(out);
        open = false;
    }

    function reset() {
        draft = {};
        onapply?.({});
        open = false;
    }
</script>

<Drawer bind:open title="Filters">
    <div class="flex flex-col gap-4">
        {#each config as field (field.key)}
            <Field label={field.label}>
                {#if field.type === 'select'}
                    <select class="kt-select" value={draft[field.key] ?? ''} onchange={(e) => set(field.key, e.currentTarget.value)}>
                        <option value="">All</option>
                        {#each field.options ?? [] as opt}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                {:else if field.type === 'resource-select'}
                    <!-- Async, paginated, searchable select (Select2-style) driven by an API resource. -->
                    {@const resource = resolve(field.resource)}
                    <!-- Remount on a resource change: Select caches the options and
                         labels it loaded, which belong to the previous route. -->
                    {#key resource}
                        <Select
                            {resource}
                            resourceParams={resolve(field.resourceParams) ?? {}}
                            labelKey={resolve(field.labelKey) ?? 'name'}
                            valueKey={resolve(field.valueKey) ?? 'id'}
                            multiple={field.multiple ?? false}
                            disabled={!!field.dependsOn && !resource}
                            placeholder={resolve(field.placeholder) ?? 'All'}
                            initialOptions={resolve(field.initialOptions) ?? []}
                            value={draft[field.key] ?? (field.multiple ? [] : null)}
                            onchange={(v) => set(field.key, v)}
                        />
                    {/key}
                {:else if field.type === 'boolean'}
                    <select class="kt-select" value={draft[field.key] ?? ''} onchange={(e) => set(field.key, e.currentTarget.value)}>
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                {:else if field.type === 'daterange'}
                    <DatePicker
                        mode="range"
                        enableTime={field.enableTime ?? false}
                        value={[draft[fromKey(field)], draft[toKey(field)]].filter(Boolean)}
                        onchange={(arr) => {
                            set(fromKey(field), arr?.[0] ?? '');
                            set(toKey(field), arr?.[1] ?? '');
                        }}
                    />
                {:else if field.type === 'numberrange'}
                    <div class="flex items-center gap-2">
                        <input type="number" class="kt-input" placeholder="From" value={draft[minKey(field)] ?? ''} oninput={(e) => set(minKey(field), e.currentTarget.value)} />
                        <span class="text-muted-foreground">–</span>
                        <input type="number" class="kt-input" placeholder="To" value={draft[maxKey(field)] ?? ''} oninput={(e) => set(maxKey(field), e.currentTarget.value)} />
                    </div>
                {:else}
                    <input type="text" class="kt-input" value={draft[field.key] ?? ''} oninput={(e) => set(field.key, e.currentTarget.value)} />
                {/if}
            </Field>
        {/each}
    </div>

    {#snippet footer()}
        <Button variant="secondary" onclick={reset}>Clear filters</Button>
        <Button variant="primary" onclick={apply}>Apply</Button>
    {/snippet}
</Drawer>
