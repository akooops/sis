<script>
    /** Streams index — the variants of a program, in a hand-set per-program order. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import StreamForm from './StreamForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    // useIndex reads `filter[program_id]` off the URL, so the drill-down link on
    // the Programs page lands here already narrowed to that program.
    const list = useIndex('api.v1.admin.streams.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    // Whatever the table is currently narrowed to — seeds both the reorder drawer
    // and a new stream's program.
    const activeProgramId = $derived(list.params.filter?.program_id ?? null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'program', label: 'Program', truncate: false },
        { key: 'slug', label: 'Slug', sortable: true, truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Select labels a preselected id
    // itself (it refetches through filter[id]), so a deep link opens the drawer
    // showing the program's name rather than a blank box.
    const filterConfig = [
        {
            key: 'program_id',
            type: 'resource-select',
            label: 'Program',
            resource: 'api.v1.admin.programs.index',
            labelKey: 'name',
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (s) => { editing = s; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (s) => { viewing = s; viewOpen = true; };
    const showActivity = (s) => { activityRow = s; activityOpen = true; };

    async function remove(s) {
        if (!(await confirm({
            body: `Delete ${s.name}? The remaining streams in its program keep their order.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.streams.destroy', s.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Streams</title></svelte:head>

<AdminLayout title="Streams">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} programId={activeProgramId} onsaved={() => list.refresh()} />
    <DetailDrawer
        bind:open={viewOpen}
        title="Stream"
        id={viewing?.id}
        heading={viewing?.name}
        fields={[
            { label: 'Program', value: viewing?.program?.name || '—' },
            { label: 'Slug', value: viewing?.slug },
            { label: 'Colour', value: viewing?.color },
            { label: 'Order', value: viewing ? `Position ${viewing.order + 1}` : null },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="stream" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search streams…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('streams.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('streams.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add stream
                </button>
            {/if}
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

<!-- `ready` comes from IndexCard: false while the fly transition runs, so the
     editor doesn't measure itself inside a transformed box. -->
{#snippet form(ready)}
    <StreamForm stream={editing} programId={activeProgramId} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No streams yet"
        emptyBody="Add a stream to offer a variant of a program."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- The swatch is the point of the colour field: seeing the palette at a
             glance is how you notice two streams picked the same blue. -->
        <div class="flex items-center gap-2.5">
            <span class="size-4 shrink-0 rounded border border-border" style="background-color: {row.color}"></span>
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="240px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'program'}
        {#if row.program}
            <Badge variant="primary">
                <ClampText value={row.program.name} maxWidth="160px" title={row.program.name} />
            </Badge>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'slug'}
        <Badge variant="secondary">
            <ClampText value={row.slug} maxWidth="160px" title={row.slug} />
        </Badge>
    {:else if column.key === 'order'}
        <!-- Position within its program, so the same number recurs across programs. -->
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('streams.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('streams.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
