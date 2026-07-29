<script>
    /**
     * Calendars index — dated calendar documents, e.g. the academic year
     * calendar for a term. A Document that also states the period it covers;
     * the default sort is by start date, newest period first.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import CalendarForm from './CalendarForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { formatDate } from '@/lib/date';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.calendars.index', { perPage: 15, sort: '-start_date' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'file_name', label: 'File', truncate: false },
        // Both dates in one cell; sort by either from the filters drawer.
        { key: 'period', label: 'Period', truncate: false },
        { key: 'is_active', label: 'Status', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'start_date', label: 'Start date' },
        { value: 'end_date', label: 'End date' },
        { value: 'created_at', label: 'Created' },
    ];

    // A daterange submits <key>_from / <key>_to — hence the controller's starts_from / starts_to.
    const filterConfig = [
        { key: 'is_active', type: 'boolean', label: 'Active' },
        { key: 'starts', type: 'daterange', label: 'Starts between' },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };

    async function remove(c) {
        if (!(await confirm({
            body: `Delete ${c.name}? Its file returns to the media library rather than being deleted.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.calendars.destroy', c.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Calendars</title></svelte:head>

<AdminLayout title="Calendars">
    <IndexCard {showForm} {toolbar} {form} {table} />

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
        title="Calendar"
        id={viewing?.id}
        heading={viewing?.name}
        fields={[
            { label: 'File', value: viewing?.file_name || '—' },
            { label: 'Start date', value: formatDate(viewing?.start_date) },
            { label: 'End date', value: formatDate(viewing?.end_date) },
            { label: 'Status', value: viewing?.is_active ? 'Active' : 'Inactive' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="calendar" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search calendars…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('calendars.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add calendar
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <CalendarForm calendar={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

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
        emptyTitle="No calendars yet"
        emptyBody="Add a calendar to publish the dates people download."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="260px" title={row.name} />
        </span>
    {:else if column.key === 'period'}
        <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm">
            <DateTime value={row.start_date} format="date" />
            <span class="text-muted-foreground">–</span>
            <DateTime value={row.end_date} format="date" />
        </span>
    {:else if column.key === 'is_active'}
        <Badge variant={row.is_active ? 'success' : 'secondary'}>{row.is_active ? 'Active' : 'Inactive'}</Badge>
    {:else if column.key === 'file_name'}
        {#if row.file_url}
            <!-- stopPropagation, or the row click opens the drawer behind the download. -->
            <a
                href={row.file_url}
                target="_blank"
                rel="noreferrer noopener"
                class="kt-link inline-flex items-center gap-1.5 text-sm"
                onclick={(e) => e.stopPropagation()}
            >
                <i class="ki-filled ki-document shrink-0"></i>
                <ClampText value={row.file_name ?? 'Download'} maxWidth="220px" title={row.file_name} />
            </a>
        {:else}
            <Badge variant="secondary">Missing</Badge>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('calendars.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('calendars.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
