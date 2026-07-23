<script>
    /** Media library index — Metronic list card; browse media as cards. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import MediaThumb from '@/components/media/MediaThumb.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { MEDIA_TYPES } from '@/lib/upload';
    import { formatFileSize } from '@/lib/format';

    /** Display labels for each media type key (was `media.tabs.*` / `media.types.*`). */
    const TAB_LABELS = { images: 'Images', audio: 'Audio', videos: 'Videos', documents: 'Documents' };
    const TYPE_LABELS = { images: 'Image', audio: 'Audio', videos: 'Video', documents: 'Document' };

    const list = useIndex('api.v1.admin.media.index', { perPage: 15, sort: '-created_at', pollMs: 0 });
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    const view = (m) => { viewing = m; viewOpen = true; };
    const showActivity = (m) => { activityRow = m; activityOpen = true; };

    // Media renders as cards, so there are no column headers to sort by — the
    // drawer is the only way in. Mirrors MediaController's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'size', label: 'Size' },
        { value: 'created_at', label: 'Uploaded' },
    ];

    // Type filter tabs (All + each media type). '' = all.
    let activeType = $state(list.params.filter?.type ?? '');
    const typeTabs = $derived([
        { id: '', label: 'All' },
        ...MEDIA_TYPES.map((ty) => ({ id: ty, label: TAB_LABELS[ty] })),
    ]);
    // The drawer round-trips the whole filter object, so clearing it there also
    // clears `type` — keep the tab strip pointing at whatever actually applied.
    function applyFilters(filter, sort) {
        activeType = filter.type ?? '';
        list.apply({ filter, sort });
    }

    function selectType(ty) {
        activeType = ty;
        const filter = { ...list.params.filter };
        if (ty) filter.type = ty;
        else delete filter.type;
        list.setFilters(filter);
    }

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Name', value: viewing.name },
                  { label: 'Type', value: TYPE_LABELS[viewing.type] },
                  { label: 'Size', value: formatFileSize(viewing.size) },
                  { label: 'Scan', value: viewing.scan_status },
                  { label: 'Attached', value: viewing.attached ? 'In use' : 'Free' },
              ]
            : [],
    );

</script>

<svelte:head><title>Saud International Schools — Media library</title></svelte:head>

<AdminLayout title="Media library">
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters
        bind:open={filtersOpen}
        config={[]}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => applyFilters(filter, sort)}
    />
    <DetailDrawer bind:open={viewOpen} title="Media library" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at}>
        {#snippet children()}
            {#if viewing?.url}
                <div class="flex flex-wrap gap-2">
                    <a class="kt-btn kt-btn-sm kt-btn-primary" href={viewing.url} target="_blank" rel="noreferrer">
                        <i class="ki-filled ki-exit-right"></i>Open file
                    </a>
                    <a class="kt-btn kt-btn-sm kt-btn-secondary" href={viewing.url} download={viewing.name}>
                        <i class="ki-filled ki-exit-down"></i>Download
                    </a>
                </div>
            {:else}
                <!-- No url until the scan passes: an infected or pending file has
                     nothing safe to hand out. -->
                <p class="text-xs text-muted-foreground">
                    This file is not available yet — its scan is {viewing?.scan_status ?? 'pending'}.
                </p>
            {/if}
        {/snippet}
    </DetailDrawer>
    <ActivityDrawer bind:open={activityOpen} subjectType="media" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search media…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        <ExportButton rows={list.rows} columns={[
            { key: 'name', label: 'Name' },
            { key: 'type', label: 'Type' },
            { key: 'size', label: 'Size' },
            { key: 'created_at', label: 'Uploaded' },
        ]} filename="media" />
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <div class="flex flex-col gap-4 p-5">
        <!-- Type tabs -->
        <div class="kt-tabs kt-tabs-line overflow-x-auto" role="tablist">
            {#each typeTabs as tt (tt.id)}
                <button
                    type="button"
                    role="tab"
                    data-kt-tab-toggle
                    class="kt-tab-toggle {activeType === tt.id ? 'active' : ''}"
                    aria-selected={activeType === tt.id}
                    onclick={() => selectType(tt.id)}
                >
                    {tt.label}
                </button>
            {/each}
        </div>

        {#if list.loading}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                {#each Array(12) as _}
                    <div class="flex flex-col overflow-hidden rounded-lg border border-border">
                        <Skeleton class="aspect-square w-full rounded-none" />
                        <div class="p-2"><Skeleton class="h-3 w-3/4" /></div>
                    </div>
                {/each}
            </div>
        {:else if list.rows.length === 0}
            <EmptyState icon="ki-filled ki-picture" title="No results found" body="No records match your criteria." />
        {:else}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                {#each list.rows as item (item.id)}
                    <div class="group relative">
                        <div class="absolute end-2 top-2 z-10 rounded-md bg-background/90 shadow-sm opacity-0 transition-opacity group-hover:opacity-100">
                            <RowActions actions={[
                                { icon: 'ki-eye', label: 'View', onclick: () => view(item) },
                                hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(item) },
                                item.url && { icon: 'ki-exit-right', label: 'Open file', onclick: () => window.open(item.url, '_blank', 'noopener,noreferrer') },
                            ].filter(Boolean)} />
                        </div>
                        <button
                            type="button"
                            class="flex w-full flex-col overflow-hidden rounded-lg border border-border text-start transition-colors hover:border-primary/50"
                            onclick={() => view(item)}
                            title={item.name}
                        >
                            <div class="flex aspect-square items-center justify-center overflow-hidden bg-muted">
                                <MediaThumb {item} />
                            </div>
                            <div class="p-2">
                                <span class="block truncate text-xs font-medium text-mono">{item.name}</span>
                            </div>
                        </button>
                    </div>
                {/each}
            </div>

            {#if list.meta && list.meta.total > 0}
                <Pagination meta={list.meta} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} />
            {/if}
        {/if}
    </div>
{/snippet}
