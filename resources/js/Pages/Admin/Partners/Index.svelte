<script>
    /** Partners index — logos shown on the public site, in a hand-set order. */
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
    import PartnerForm from './PartnerForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.partners.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
        { key: 'url', label: 'Website', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'name', label: 'Name' },
        { value: 'created_at', label: 'Created' },
    ];

    // Sort-only drawer: a partner is a name, a link and a logo — there is
    // nothing to filter by that the search box doesn't already cover.
    const filterConfig = [];

    const create = () => { editing = null; showForm = true; };
    const edit = (p) => { editing = p; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (p) => { viewing = p; viewOpen = true; };
    const showActivity = (p) => { activityRow = p; activityOpen = true; };

    async function remove(p) {
        if (!(await confirm({
            body: `Delete ${p.name}? The remaining partners keep their order.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.partners.destroy', p.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Partners</title></svelte:head>

<AdminLayout title="Partners">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} onsaved={() => list.refresh()} />
    <DetailDrawer
        bind:open={viewOpen}
        title="Partner"
        id={viewing?.id}
        avatar={{ src: viewing?.logo_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: `Position ${viewing.order + 1}`, variant: 'secondary' } : null}
        fields={[
            { label: 'Website', value: viewing?.url },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="partner" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search partners…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('partners.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('partners.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add partner
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
    <PartnerForm partner={editing} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No partners yet"
        emptyBody="Add a partner to show its logo on the public site."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- Logo + name in one cell, like the user avatar column. Not <Avatar>:
             that crops to a circle, which mangles a wide partner logo. -->
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-background">
                <img src={row.logo_url} alt="" class="size-full object-contain p-1" />
            </span>
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="240px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'order'}
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else if column.key === 'url'}
        <a href={row.url} target="_blank" rel="noreferrer noopener" class="kt-link text-sm" onclick={(e) => e.stopPropagation()}>
            <ClampText value={row.url} maxWidth="260px" title={row.url} />
        </a>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('partners.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('partners.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
