<script>
    /** Brand asset groups — the folders a brand's downloadable kit is filed under. */
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
    import BrandAssetGroupForm from './BrandAssetGroupForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    // useIndex reads `filter[brand_id]` off the URL, so the drill-down link on the
    // Brands page lands here already narrowed to that brand.
    const list = useIndex('api.v1.admin.brand-asset-groups.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    // Whatever the table is currently narrowed to — seeds both the reorder drawer
    // and a new group's brand.
    const activeBrandId = $derived(list.params.filter?.brand_id ?? null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'brand', label: 'Brand', truncate: false },
        // A withCount, not a column — nothing to sort on.
        { key: 'assets_count', label: 'Assets', width: '100px', truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Select labels a preselected id
    // itself (it refetches through filter[id]), so a deep link opens the drawer
    // showing the brand's name rather than a blank box.
    const filterConfig = [
        {
            key: 'brand_id',
            type: 'resource-select',
            label: 'Brand',
            resource: 'api.v1.admin.brands.index',
            labelKey: 'name',
            placeholder: 'All brands',
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (g) => { editing = g; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (g) => { viewing = g; viewOpen = true; };
    const showActivity = (g) => { activityRow = g; activityOpen = true; };

    /** Hand the child module its own page, pre-filtered to this group. */
    const drillDown = (name, g) => router.visit(`${route(name)}?filter[brand_asset_group_id]=${g.id}`);

    async function remove(g) {
        if (!(await confirm({
            body: `Delete ${g.name}? The assets in it are deleted with it. Their uploaded files are returned to the media library, not destroyed. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.brand-asset-groups.destroy', g.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Brand asset groups</title></svelte:head>

<AdminLayout title="Brand asset groups">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} brandId={activeBrandId} onsaved={() => list.refresh()} />
    <!-- No title row: the drawer is a record summary, not a translation preview —
         that is what the form's Translations tab is for. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Asset group"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: `Position ${viewing.order + 1}`, variant: 'secondary' } : null}
        fields={[
            { label: 'Brand', value: viewing?.brand?.name || '' },
            { label: 'Assets', value: String(viewing?.assets_count ?? 0) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="brand_asset_group" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search asset groups…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('brand-asset-groups.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('brand-asset-groups.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add group
                </button>
            {/if}
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <BrandAssetGroupForm group={editing} brandId={activeBrandId} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No asset groups yet"
        emptyBody="Add a group to file a brand's assets, like Fonts or Logos."
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
    {:else if column.key === 'brand'}
        {#if row.brand}
            <Badge variant="primary">
                <ClampText value={row.brand.name} maxWidth="160px" title={row.brand.name} />
            </Badge>
        {:else}

        {/if}
    {:else if column.key === 'assets_count'}
        <Badge variant="secondary">{row.assets_count ?? 0}</Badge>
    {:else if column.key === 'order'}
        <!-- Position within its brand, so the same number recurs across brands. -->
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('brand-asset-groups.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('brand-assets.index') && { icon: 'ki-files', label: 'Assets', onclick: () => drillDown('web.admin.brand-assets.index', row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('brand-asset-groups.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
