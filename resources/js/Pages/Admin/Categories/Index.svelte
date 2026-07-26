<script>
    /** Categories index — filing labels, scoped by the content type they classify. */
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
    import { CATEGORY_TYPE_LABELS, CATEGORY_TYPE_OPTIONS, CATEGORY_TYPE_VARIANTS } from '@/lib/category';
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
        { key: 'type', label: 'Type', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'name', label: 'Name' },
        { value: 'code', label: 'Code' },
        { value: 'type', label: 'Type' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        { key: 'type', type: 'select', label: 'Type', options: CATEGORY_TYPE_OPTIONS },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };

    async function remove(c) {
        if (!(await confirm({
            body: `Delete ${c.name}? Anything filed under it becomes uncategorised — nothing else is deleted.`,
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
        badge={viewing ? { label: viewing.type_label ?? viewing.type, variant: CATEGORY_TYPE_VARIANTS[viewing.type] ?? 'secondary' } : null}
        fields={[
            { label: 'Code', value: viewing?.code },
            { label: 'Type', value: viewing?.type_label ?? viewing?.type },
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
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="240px" title={row.name} />
        </span>
    {:else if column.key === 'code'}
        <Badge variant="secondary">
            <ClampText value={row.code} maxWidth="180px" title={row.code} />
        </Badge>
    {:else if column.key === 'type'}
        <Badge variant={CATEGORY_TYPE_VARIANTS[row.type] ?? 'secondary'}>
            {row.type_label ?? CATEGORY_TYPE_LABELS[row.type] ?? row.type}
        </Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('categories.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('categories.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
