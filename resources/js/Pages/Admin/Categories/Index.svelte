<script>
    /** Categories index — one flat list of filing labels, shared by every module. */
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
    import CategoryForm from './CategoryForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.categories.index', { perPage: 15, sort: 'name' });

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
        { key: 'code', label: 'Code', sortable: true, truncate: false },
        { key: 'is_default', label: 'Default', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'name', label: 'Name' },
        { value: 'code', label: 'Code' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        { key: 'is_default', type: 'boolean', label: 'Default' },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };

    async function remove(c) {
        if (!(await confirm({
            body: `Delete ${c.name}? Anything filed under it moves to the default category — nothing else is deleted.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.categories.destroy', c.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Categories</title></svelte:head>

<AdminLayout title="Categories">
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
        title="Category"
        id={viewing?.id}
        heading={viewing?.name}
        fields={[
            { label: 'Code', value: viewing?.code },
            { label: 'Colour', value: viewing?.color },
            { label: 'Default', value: viewing?.is_default ? 'Yes' : 'No' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="category" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search categories…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('categories.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add category
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <CategoryForm category={editing} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No categories yet"
        emptyBody="Create a category to start filing content under it."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- The swatch is the point of the colour field: seeing the palette at a
             glance is how you notice two categories picked the same blue. -->
        <div class="flex items-center gap-2.5">
            <span class="size-4 shrink-0 rounded border border-border" style="background-color: {row.color}"></span>
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="240px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'code'}
        <Badge variant="secondary">
            <ClampText value={row.code} maxWidth="180px" title={row.code} />
        </Badge>
    {:else if column.key === 'is_default'}
        {#if row.is_default}
            <Badge variant="primary">Default</Badge>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('categories.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        // The default is what everything falls back to — there is nowhere to move
        // its content, so it has no Delete at all rather than one that 422s.
        hasPermission('categories.destroy') && !row.is_default
            && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
