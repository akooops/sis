<script>
    /**
     * ActivityDrawer — "who did what to this record, and when".
     *
     * Drop one next to a module's DetailDrawer and point it at the row:
     *
     *   <ActivityDrawer bind:open={activityOpen} subjectType="api_key"
     *                   subjectId={activeRow?.id} title={activeRow?.name} />
     *
     * It filters in place (causer, event, date range) rather than opening a
     * nested Filters drawer — Filters *is* a Drawer, and stacking them is wrong.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import ActivityTimeline from './ActivityTimeline.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { ACTIVITY_EVENTS, CAUSER_RESOURCES, EVENT_LABELS } from '@/lib/activity';
    import { untrack } from 'svelte';

    let { open = $bindable(false), subjectType = null, subjectId = null, title = null } = $props();

    // readUrl:false — the drawer must not read or fight the page's own query
    // string. immediate:false — a mounted-but-closed drawer shouldn't fetch.
    const list = useIndex('api.v1.admin.activities.index', {
        perPage: 20,
        sort: '-created_at',
        readUrl: false,
        immediate: false,
    });

    let causerType = $state('');
    let causerId = $state(null);
    let event = $state('');
    let dates = $state([]);

    const causerResource = $derived(causerType ? CAUSER_RESOURCES[causerType] : null);
    const activeFilters = $derived(
        [causerType, causerId, event, dates?.length ? '1' : ''].filter(Boolean).length,
    );

    function apply() {
        list.setFilters({
            subject_type: subjectType,
            subject_id: subjectId,
            causer_type: causerType || null,
            causer_id: causerId || null,
            event: event || null,
            created_from: dates?.[0] ?? null,
            created_to: dates?.[1] ?? null,
        });
    }

    function reset() {
        causerType = '';
        causerId = null;
        event = '';
        dates = [];
        apply();
    }

    // useIndex reads `filter` once at init, so a drawer reused across rows would
    // otherwise keep showing the first subject it was opened for.
    $effect(() => {
        if (!open || !subjectId) return;
        subjectId;
        subjectType;
        untrack(() => apply());
    });

    function onCauserTypeChange(value) {
        causerType = value;
        causerId = null; // a causer picked for the old type can't survive the switch
    }
</script>

<Drawer bind:open title="Activity" width="w-[620px]">
    {#snippet header()}
        <div class="flex flex-col gap-0.5">
            <h3 class="text-base font-semibold text-mono">Activity</h3>
            {#if title}
                <span class="text-xs text-muted-foreground">{title}</span>
            {/if}
        </div>
    {/snippet}

    <div class="flex flex-col gap-4">
        <!-- filters -->
        <div class="flex flex-col gap-3 rounded-lg border border-border bg-muted/30 p-3">
            <div class="grid grid-cols-2 gap-3">
                <Field label="Performed by (type)">
                    <select class="kt-select" value={causerType} onchange={(e) => onCauserTypeChange(e.currentTarget.value)}>
                        <option value="">All</option>
                        <option value="user">User</option>
                        <option value="api_key">API key</option>
                    </select>
                </Field>

                <Field label="Performed by">
                    {#key causerType}
                        <Select
                            resource={causerResource?.route ?? null}
                            labelKey={causerResource?.labelKey ?? 'name'}
                            disabled={!causerResource}
                            placeholder={causerResource ? 'All' : 'Choose a type first'}
                            value={causerId}
                            onchange={(v) => (causerId = v)}
                        />
                    {/key}
                </Field>

                <Field label="Event">
                    <select class="kt-select" value={event} onchange={(e) => (event = e.currentTarget.value)}>
                        <option value="">All</option>
                        {#each ACTIVITY_EVENTS as value (value)}
                            <option {value}>{EVENT_LABELS[value] ?? value}</option>
                        {/each}
                    </select>
                </Field>

                <Field label="Date">
                    <DatePicker mode="range" value={dates} onchange={(v) => (dates = v ?? [])} />
                </Field>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span class="text-xs text-muted-foreground">
                    {#if list.meta?.total}
                        Showing {list.meta.from}–{list.meta.to} of {list.meta.total}
                    {/if}
                </span>
                <div class="flex gap-2">
                    <Button variant="secondary" size="sm" onclick={reset} disabled={!activeFilters}>
                        Clear filters
                    </Button>
                    <Button variant="primary" size="sm" onclick={apply}>Apply</Button>
                </div>
            </div>
        </div>

        <ActivityTimeline rows={list.rows} loading={list.loading} />

        {#if list.meta?.total > list.meta?.per_page}
            <Pagination meta={list.meta} onPageChange={(p) => list.goToPage(p)} />
        {/if}
    </div>
</Drawer>
