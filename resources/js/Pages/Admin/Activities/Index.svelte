<script>
    /**
     * The audit trail across every module. Read-only by design: no form snippet,
     * no row actions — rows are written by the observers, never by hand.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import ActivityDiff from '@/components/activity/ActivityDiff.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        ACTIVITY_EVENTS,
        ACTIVITY_LOG_NAMES,
        CAUSER_RESOURCES,
        activityMessage,
        eventIcon,
        eventVariant,
        hasDiff,
        EVENT_LABELS,
        LOG_NAME_LABELS,
        SUBJECT_TYPE_LABELS,
    } from '@/lib/activity';

    const list = useIndex('api.v1.admin.activities.index', { perPage: 25, sort: '-created_at' });

    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);

    const view = (row) => {
        viewing = row;
        viewOpen = true;
    };

    const columns = [
        { key: 'created_at', label: 'Date', sortable: true, truncate: false, width: '170px' },
        { key: 'event', label: 'Event', sortable: true, truncate: false, width: '150px' },
        { key: 'description', label: 'Activity' },
        { key: 'causer', label: 'Performed by', truncate: false, width: '200px' },
        { key: 'subject', label: 'Record', truncate: false, width: '190px' },
    ];

    const filterConfig = [
        {
            key: 'log_name',
            type: 'select',
            label: 'Module',
            options: ACTIVITY_LOG_NAMES.map((value) => ({ value, label: LOG_NAME_LABELS[value] ?? value })),
        },
        {
            key: 'event',
            type: 'select',
            label: 'Event',
            options: ACTIVITY_EVENTS.map((value) => ({ value, label: EVENT_LABELS[value] ?? value })),
        },
        {
            key: 'subject_type',
            type: 'select',
            label: 'Record type',
            options: Object.keys(CAUSER_RESOURCES)
                .concat(['role', 'permission', 'media'])
                .map((value) => ({ value, label: SUBJECT_TYPE_LABELS[value] ?? value })),
        },
        {
            key: 'causer_type',
            type: 'select',
            label: 'Performed by (type)',
            options: Object.keys(CAUSER_RESOURCES).map((value) => ({
                value,
                label: SUBJECT_TYPE_LABELS[value] ?? value,
            })),
        },
        {
            // Follows causer_type: each causer kind is searched through its own
            // module's index route, so there is no extra endpoint to maintain.
            key: 'causer_id',
            type: 'resource-select',
            label: 'Performed by',
            dependsOn: 'causer_type',
            resource: (d) => CAUSER_RESOURCES[d.causer_type]?.route ?? null,
            labelKey: (d) => CAUSER_RESOURCES[d.causer_type]?.labelKey ?? 'name',
            placeholder: (d) => (d.causer_type ? 'All' : 'Choose a type first'),
        },
        { key: 'created', type: 'daterange', label: 'Date' },
    ];
</script>

<AdminLayout title="Activity log">
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />

    <Drawer bind:open={viewOpen} title="Activity" width="w-[560px]">
        {#if viewing}
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant={eventVariant(viewing.event)}>{EVENT_LABELS[viewing.event] ?? viewing.event}</Badge>
                    <span class="text-sm text-mono">{activityMessage(viewing)}</span>
                </div>

                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                    <dt class="text-muted-foreground">Module</dt>
                    <dd class="text-mono">{LOG_NAME_LABELS[viewing.log_name] ?? viewing.log_name}</dd>

                    <dt class="text-muted-foreground">Performed by</dt>
                    <dd class="text-mono">
                        {#if viewing.causer_id}
                            {viewing.causer_name ?? viewing.causer_id}
                            <span class="text-muted-foreground">({SUBJECT_TYPE_LABELS[viewing.causer_type] ?? viewing.causer_type})</span>
                        {:else}
                            System
                        {/if}
                    </dd>

                    <dt class="text-muted-foreground">Record</dt>
                    <dd class="text-mono">
                        {#if viewing.subject_id}
                            {viewing.subject_label ?? viewing.subject_id}
                            <span class="text-muted-foreground">({SUBJECT_TYPE_LABELS[viewing.subject_type] ?? viewing.subject_type})</span>
                        {:else}
                            —
                        {/if}
                    </dd>

                    <dt class="text-muted-foreground">Date</dt>
                    <dd class="text-mono"><DateTime value={viewing.created_at} /></dd>
                </dl>

                {#if hasDiff(viewing.properties)}
                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            What changed
                        </span>
                        <ActivityDiff properties={viewing.properties} />
                    </div>
                {:else}
                    <p class="text-xs text-muted-foreground">No field changes were recorded.</p>
                {/if}
            </div>
        {/if}
    </Drawer>
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search activities" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <button
            class="kt-btn kt-btn-sm kt-btn-ghost"
            onclick={() => (filtersOpen = true)}
            aria-label="Filter"
        >
            <i class="ki-filled ki-filter"></i>
        </button>
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <DataTable
        {columns}
        rows={list.rows}
        loading={list.loading}
        meta={list.meta}
        sort={list.params.sort}
        onSort={list.toggleSort}
        onPageChange={list.goToPage}
        onPerPageChange={list.setPerPage}
        onRowClick={view}
        emptyIcon="ki-filled ki-time"
        emptyTitle="No activity yet"
        emptyBody="Changes will appear here as soon as someone makes one."
        {cells}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else if column.key === 'event'}
        <Badge variant={eventVariant(row.event)}>
            <i class="{eventIcon(row.event)} me-1"></i>{EVENT_LABELS[row.event] ?? row.event}
        </Badge>
    {:else if column.key === 'description'}
        {activityMessage(row)}
    {:else if column.key === 'causer'}
        {#if row.causer_id}
            <span class="inline-flex items-center gap-2">
                <Avatar name={row.causer_name ?? ''} size="xs" />
                <span class="truncate text-mono">{row.causer_name ?? row.causer_id}</span>
            </span>
        {:else}
            <span class="text-muted-foreground">System</span>
        {/if}
    {:else if column.key === 'subject'}
        {#if row.subject_id}
            <span class="inline-flex items-center gap-2">
                <IdBadge id={row.subject_id} />
                <span class="text-xs text-muted-foreground">{SUBJECT_TYPE_LABELS[row.subject_type] ?? row.subject_type}</span>
            </span>
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}
