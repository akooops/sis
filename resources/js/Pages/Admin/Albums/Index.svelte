<script>
    /** Albums index — galleries of images, video and audio, with a publish workflow. */
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
    import AlbumForm from './AlbumForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { ALBUM_STATUS_LABELS, ALBUM_STATUS_VARIANTS } from '@/lib/album';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.albums.index', { perPage: 15, sort: '-created_at' });

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
        { key: 'slug', label: 'Slug', sortable: true, truncate: false },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
        { key: 'published_at', label: 'Published', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'status', label: 'Status' },
        { value: 'published_at', label: 'Published' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(ALBUM_STATUS_LABELS).map(([value, label]) => ({ value, label })),
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
            body: `Delete ${a.name}? Its uploaded images are returned to the media library, not destroyed. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.albums.destroy', a.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Albums</title></svelte:head>

<AdminLayout title="Albums">
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
        title="Album"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: ALBUM_STATUS_LABELS[viewing.status] ?? viewing.status, variant: ALBUM_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={[
            { label: 'Slug', value: viewing?.slug },
            { label: 'Files', value: String(viewing?.files?.length ?? 0) },
            { label: 'Published at', value: viewing?.published_at ?? '—' },
            { label: 'Stylesheet', value: viewing?.css_url || '—' },
        ]}
        collections={viewing?.files?.length ? [{ label: 'Album files', items: viewing.files }] : []}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="album" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search albums…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('albums.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add album
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

<!-- `ready` comes from IndexCard: false while the fly transition runs, so the
     editor doesn't measure itself inside a transformed box. -->
{#snippet form(ready)}
    <AlbumForm album={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No albums yet"
        emptyBody="Create an album to get started."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex items-center gap-3">
            {#if row.thumbnail_url}
                <img src={row.thumbnail_url} alt="" class="size-8 shrink-0 rounded object-cover" />
            {/if}
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="200px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'slug'}
        <Badge variant="secondary">
            <ClampText value={row.slug} maxWidth="160px" title={row.slug} />
        </Badge>
    {:else if column.key === 'files_count'}
        <!-- Counted from `files` rather than a dedicated column: the DTO already
             ships the gallery, so a separate count would be a second source of
             truth for the same number. -->
        <Badge variant={row.files?.length ? 'primary' : 'secondary'}>
            <i class="ki-filled ki-picture me-1"></i>{row.files?.length ?? 0}
        </Badge>
    {:else if column.key === 'status'}
        <Badge variant={ALBUM_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {ALBUM_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'published_at'}
        {#if row.published_at}<DateTime value={row.published_at} />{:else}<span class="text-xs text-muted-foreground">—</span>{/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('albums.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('albums.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
