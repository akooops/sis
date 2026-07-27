<script>
    /** Banners index — the hero slides, in a hand-set order. */
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
    import BannerForm from './BannerForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { LINKABLE_OPTIONS, linkKind, linkKindLabel, linkTarget } from '@/lib/linkable';

    const list = useIndex('api.v1.admin.banners.index', { perPage: 15, sort: 'order' });

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
        { key: 'link', label: 'Link', truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'created_at', label: 'Created' },
    ];

    // Records only: the server matches linkable_type by MorphType alias, and
    // 'url' is a picker kind it would resolve to nothing.
    const filterConfig = [
        {
            key: 'linkable_type',
            type: 'select',
            label: 'Link',
            options: LINKABLE_OPTIONS.filter((o) => o.value !== 'url'),
        },
    ];

    /** One line for the drawer: the address, or the type and the record's name. */
    function linkSummary(row) {
        if (!row) return '—';
        const kind = linkKind(row);
        if (!kind) return 'No link';
        if (kind === 'url') return row.url;

        return `${linkKindLabel(kind)} — ${linkTarget(row) ?? '—'}`;
    }

    const create = () => { editing = null; showForm = true; };
    const edit = (b) => { editing = b; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (b) => { viewing = b; viewOpen = true; };
    const showActivity = (b) => { activityRow = b; activityOpen = true; };

    async function remove(b) {
        if (!(await confirm({
            body: `Delete ${b.name}? The remaining banners keep their order.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.banners.destroy', b.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Banners</title></svelte:head>

<AdminLayout title="Banners">
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
        title="Banner"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        fields={[
            { label: 'Link', value: linkSummary(viewing) },
            { label: 'Video', value: viewing?.video_url ? 'Yes' : 'No' },
            { label: 'Position', value: viewing ? String(viewing.order + 1) : '—' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="banner" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search banners…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('banners.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('banners.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add banner
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
    <BannerForm banner={editing} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No banners yet"
        emptyBody="Add a banner to show a slide on the public site."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- Artwork + name in one cell. Not <Avatar>: that crops to a circle,
             which mangles a wide slide. -->
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-background">
                {#if row.thumbnail_url}
                    <img src={row.thumbnail_url} alt="" class="size-full object-contain p-1" />
                {:else}
                    <i class="ki-filled ki-picture text-muted-foreground"></i>
                {/if}
            </span>
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="220px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'link'}
        {#if row.url}
            <a href={row.url} target="_blank" rel="noreferrer noopener" class="kt-link text-sm" onclick={(e) => e.stopPropagation()}>
                <ClampText value={row.url} maxWidth="240px" title={row.url} />
            </a>
        {:else if row.linkable_type}
            <div class="flex items-center gap-2">
                <Badge variant="secondary">{linkKindLabel(row.linkable_type)}</Badge>
                <!-- linkable is null once the target is deleted; the id survives. -->
                <span class="min-w-0 text-sm text-mono">
                    <ClampText value={linkTarget(row)} maxWidth="180px" title={linkTarget(row)} />
                </span>
            </div>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'order'}
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('banners.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('banners.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
