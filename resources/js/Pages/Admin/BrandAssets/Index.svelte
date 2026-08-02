<script>
    /** Brand assets — the downloadable files filed under a brand's asset groups. */
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
    import BrandAssetForm from './BrandAssetForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    // useIndex reads `filter[brand_asset_group_id]` off the URL, so the drill-down
    // link on the Asset groups page lands here already narrowed to that group.
    const list = useIndex('api.v1.admin.brand-assets.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    // Whatever the table is currently narrowed to — seeds both the reorder drawer
    // and a new asset's group.
    const activeGroupId = $derived(list.params.filter?.brand_asset_group_id ?? null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'group', label: 'Group', truncate: false },
        { key: 'brand', label: 'Brand', truncate: false },
        { key: 'file_name', label: 'File', truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. The group select follows the brand:
    // `dependsOn` drops a group left over from the previous brand, and the pinned
    // filter narrows what the picker offers — an asset's group is only meaningful
    // inside one brand, so offering all of them would invite an impossible pair.
    const filterConfig = [
        {
            key: 'brand_id',
            type: 'resource-select',
            label: 'Brand',
            resource: 'api.v1.admin.brands.index',
            labelKey: 'name',
            placeholder: 'All brands',
        },
        {
            key: 'brand_asset_group_id',
            type: 'resource-select',
            label: 'Group',
            dependsOn: 'brand_id',
            resource: 'api.v1.admin.brand-asset-groups.index',
            labelKey: 'name',
            resourceParams: (d) => (d.brand_id ? { filter: { brand_id: d.brand_id } } : {}),
            placeholder: (d) => (d.brand_id ? 'All groups in this brand' : 'All groups'),
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (a) => { editing = a; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (a) => { viewing = a; viewOpen = true; };
    const showActivity = (a) => { activityRow = a; activityOpen = true; };

    async function remove(a) {
        if (!(await confirm({
            body: `Delete ${a.name}? Its file returns to the media library rather than being deleted.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.brand-assets.destroy', a.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Brand assets</title></svelte:head>

<AdminLayout title="Brand assets">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} groupId={activeGroupId} onsaved={() => list.refresh()} />
    <!-- No title row: the drawer is a record summary, not a translation preview —
         that is what the form's Translations tab is for. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Brand asset"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: `Position ${viewing.order + 1}`, variant: 'secondary' } : null}
        fields={[
            { label: 'Group', value: viewing?.group?.name || '' },
            { label: 'Brand', value: viewing?.group?.brand?.name || '' },
            { label: 'File', value: viewing?.file_name || '' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="brand_asset" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search assets…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('brand-assets.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('brand-assets.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add asset
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
    <BrandAssetForm asset={editing} groupId={activeGroupId} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No assets yet"
        emptyBody="Add an asset to publish a downloadable file in this group."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="220px" title={row.name} />
        </span>
    {:else if column.key === 'group'}
        {#if row.group}
            <Badge variant="primary">
                <ClampText value={row.group.name} maxWidth="150px" title={row.group.name} />
            </Badge>
        {:else}

        {/if}
    {:else if column.key === 'brand'}
        {#if row.group?.brand}
            <Badge variant="secondary">
                <ClampText value={row.group.brand.name} maxWidth="150px" title={row.group.brand.name} />
            </Badge>
        {:else}

        {/if}
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
                <ClampText value={row.file_name ?? 'Download'} maxWidth="200px" title={row.file_name} />
            </a>
        {:else}
            <Badge variant="secondary">Missing</Badge>
        {/if}
    {:else if column.key === 'order'}
        <!-- Position within its group, so the same number recurs across groups. -->
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('brand-assets.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('brand-assets.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
