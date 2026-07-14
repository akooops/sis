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
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), config = [], values = {}, onapply } = $props();

    // Working copy; seeded from applied values whenever the drawer opens.
    let draft = $state({});

    $effect(() => {
        if (open) draft = { ...values };
    });

    function set(key, value) {
        draft = { ...draft, [key]: value };
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

<Drawer bind:open title={$t('common.filters.title')}>
    <div class="flex flex-col gap-4">
        {#each config as field (field.key)}
            <Field label={field.label}>
                {#if field.type === 'select'}
                    <select class="kt-select" value={draft[field.key] ?? ''} onchange={(e) => set(field.key, e.currentTarget.value)}>
                        <option value="">{$t('common.filters.all')}</option>
                        {#each field.options ?? [] as opt}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                {:else if field.type === 'resource-select'}
                    <!-- Async, paginated, searchable select (Select2-style) driven by an API resource. -->
                    <Select
                        resource={field.resource}
                        resourceParams={field.resourceParams ?? {}}
                        labelKey={field.labelKey ?? 'name'}
                        valueKey={field.valueKey ?? 'id'}
                        multiple={field.multiple ?? false}
                        placeholder={field.placeholder ?? $t('common.filters.all')}
                        initialOptions={field.initialOptions ?? []}
                        value={draft[field.key] ?? (field.multiple ? [] : null)}
                        onchange={(v) => set(field.key, v)}
                    />
                {:else if field.type === 'boolean'}
                    <select class="kt-select" value={draft[field.key] ?? ''} onchange={(e) => set(field.key, e.currentTarget.value)}>
                        <option value="">{$t('common.filters.all')}</option>
                        <option value="1">{$t('common.filters.yes')}</option>
                        <option value="0">{$t('common.filters.no')}</option>
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
                        <input type="number" class="kt-input" placeholder={$t('common.filters.from')} value={draft[minKey(field)] ?? ''} oninput={(e) => set(minKey(field), e.currentTarget.value)} />
                        <span class="text-muted-foreground">–</span>
                        <input type="number" class="kt-input" placeholder={$t('common.filters.to')} value={draft[maxKey(field)] ?? ''} oninput={(e) => set(maxKey(field), e.currentTarget.value)} />
                    </div>
                {:else}
                    <input type="text" class="kt-input" value={draft[field.key] ?? ''} oninput={(e) => set(field.key, e.currentTarget.value)} />
                {/if}
            </Field>
        {/each}
    </div>

    {#snippet footer()}
        <Button variant="secondary" onclick={reset}>{$t('common.filters.clear')}</Button>
        <Button variant="primary" onclick={apply}>{$t('common.actions.apply')}</Button>
    {/snippet}
</Drawer>
