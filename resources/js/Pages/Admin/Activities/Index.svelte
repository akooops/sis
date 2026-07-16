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
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDiff from '@/components/activity/ActivityDiff.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
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

    // Same shape as every other module's view drawer: DetailDrawer renders the
    // id row, clamps each value and offers copy/expand. An activity's created_at
    // IS the event's date, so it reads as "Date" rather than a Created/Updated
    // pair that would both show the same instant.
    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Module', value: LOG_NAME_LABELS[viewing.log_name] ?? viewing.log_name },
                  { label: 'Event', value: EVENT_LABELS[viewing.event] ?? viewing.event },
                  { label: 'Type', value: SUBJECT_TYPE_LABELS[viewing.subject_type] ?? viewing.subject_type ?? '—' },
                  {
                      label: 'Record',
                      value: viewing.subject_id ? (viewing.subject_label ?? viewing.subject_id) : '—',
                  },
                  {
                      label: 'Performed by',
                      value: viewing.causer_id
                          ? `${viewing.causer_name ?? viewing.causer_id} (${SUBJECT_TYPE_LABELS[viewing.causer_type] ?? viewing.causer_type})`
                          : 'System',
                  },
                  { label: 'Date', date: viewing.created_at },
              ]
            : [],
    );

    const columns = [
        { key: 'created_at', label: 'Date', sortable: true, truncate: false, width: '170px' },
        { key: 'event', label: 'Event', sortable: true, truncate: false, width: '150px' },
        { key: 'subject_type', label: 'Type', truncate: false, width: '110px' },
        { key: 'description', label: 'Activity' },
        { key: 'causer', label: 'Performed by', truncate: false, width: '200px' },
        { key: 'subject', label: 'Record', truncate: false, width: '190px' },
    ];

    // Mirrors the controller's allowedSorts — the drawer and the table headers
    // drive the same `sort`, so a column here must be sortable server-side.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'event', label: 'Event' },
        { value: 'log_name', label: 'Module' },
        { value: 'created_at', label: 'Date' },
    ];

    // Mirrors the controller's allowedFilters. Who did it and when — the rest of
    // the row (module, event, record) is right there in the table to read.
    const filterConfig = [
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
    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />

    <DetailDrawer
        bind:open={viewOpen}
        title="Activity"
        width="w-[560px]"
        id={viewing?.id}
        heading={viewing ? activityMessage(viewing) : null}
        badge={viewing ? { label: EVENT_LABELS[viewing.event] ?? viewing.event, variant: eventVariant(viewing.event) } : null}
        fields={viewFields}
    >
        {#snippet children()}
            {#if hasDiff(viewing?.properties)}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        What changed
                    </span>
                    <ActivityDiff properties={viewing.properties} />
                </div>
            {:else}
                <p class="text-xs text-muted-foreground">No field changes were recorded.</p>
            {/if}
        {/snippet}
    </DetailDrawer>
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search activities" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
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
    {:else if column.key === 'subject_type'}
        {#if row.subject_type}
            <Badge variant="secondary">{SUBJECT_TYPE_LABELS[row.subject_type] ?? row.subject_type}</Badge>
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
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
