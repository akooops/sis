<script>
    /**
     * Talent pools — DISCOVERED, never declared.
     *
     * A nightly job clusters candidate embeddings (k-means in PHP; MySQL has no
     * vector type) and an LLM names each result, so HR reads "Primary Maths &
     * Science" rather than "Cluster 7". They exist to BOUND THE MATCH MATRIX:
     * scoring 500 candidates against 30 postings is 15,000 LLM calls, and scoring
     * only within a shared pool keeps it linear.
     *
     * SO THERE IS NO ADD BUTTON, and its absence is the design rather than an
     * omission — no `clusters.store` permission, no store route, and a hand-made
     * pool would have no centroid to gather anyone with.
     *
     * THE EMPTY STATE IS THE WHOLE PAGE TODAY. Clustering stands down below
     * Cluster::MIN_CANDIDATES (30) embedded candidates, and with no AI provider
     * configured nothing has an embedding at all — so the page explains the
     * threshold and shows how far off it is, rather than reading as broken.
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
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import ClusterForm from './ClusterForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    /** Mirrors Cluster::MIN_CANDIDATES. Below this, clustering is noise. */
    const MIN_CANDIDATES = 30;

    const list = useIndex('api.v1.admin.clusters.index', { perPage: 15, sort: '-size' });

    const canCountCandidates = hasPermission('candidates.index');

    /*
     * A one-row probe purely for the empty state's headline number.
     *
     * `meta.total` is the whole point — per_page 1 keeps it to a single row, and
     * it only ever runs while there are no pools to show. One extra request buys
     * the one question an admin looking at an empty page will actually have.
     */
    const embedded = useIndex('api.v1.admin.candidates.index', {
        perPage: 1,
        filter: { embedded: '1' },
        readUrl: false,
        immediate: canCountCandidates,
        pollMs: 0,
    });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    let membersOpen = $state(false);
    let postingsOpen = $state(false);
    let pool = $state(null);

    const isEmpty = $derived(!list.loading && list.meta?.total === 0);
    const embeddedTotal = $derived(embedded.meta?.total ?? null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '110px', truncate: false },
        { key: 'name', label: 'Pool', sortable: true, truncate: false },
        { key: 'candidates_count', label: 'Candidates', align: 'center', width: '110px', truncate: false },
        { key: 'job_offers_count', label: 'Postings', align: 'center', width: '100px', truncate: false },
        { key: 'is_locked', label: 'Naming', truncate: false, width: '110px' },
        { key: 'rebuilt_at', label: 'Rebuilt', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'size', label: 'Size' },
        { value: 'rebuilt_at', label: 'Rebuilt' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        { key: 'is_locked', type: 'boolean', label: 'Name locked' },
        { key: 'size', type: 'numberrange', label: 'Size' },
    ];

    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };
    const showMembers = (c) => { pool = c; membersOpen = true; };
    const showPostings = (c) => { pool = c; postingsOpen = true; };

    async function remove(row) {
        if (!(await confirm({
            body: `Delete the pool ${row.name}? Its memberships go with it; no candidate or posting is deleted. The next nightly rebuild may recreate a pool like it. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.clusters.destroy', row.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Talent pools</title></svelte:head>

<AdminLayout title="Talent pools">
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
        title="Talent pool"
        id={viewing?.id}
        heading={viewing?.name}
        subheading={viewing?.description}
        badge={viewing ? { label: viewing.is_locked ? 'Name locked' : 'Named by the rebuild', variant: viewing.is_locked ? 'primary' : 'secondary' } : null}
        fields={[
            { label: 'Candidates now', value: String(viewing?.candidates_count ?? 0) },
            { label: 'Postings now', value: String(viewing?.job_offers_count ?? 0) },
            // Deliberately shown next to the live count: they disagree whenever
            // someone has removed a member since the last run.
            { label: 'Size at last rebuild', value: String(viewing?.size ?? 0) },
            { label: 'Centroid', value: viewing?.has_centroid ? 'Present' : 'None — this pool cannot place a posting' },
            { label: 'Last rebuilt', value: viewing?.rebuilt_at ? '' : 'Never', date: viewing?.rebuilt_at ?? null },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />

    <ActivityDrawer bind:open={activityOpen} subjectType="cluster" subjectId={activityRow?.id} title={activityRow?.name} />

    <!-- Membership is READ AND REMOVE. There is no assign control because
         RebuildClusters deletes every membership row before reassigning, so an
         add would be a feature that undoes itself at 03:00. -->
    <PivotDrawer
        bind:open={membersOpen}
        title={`Candidates in ${pool?.name ?? ''}`}
        width="w-[560px]"
        parentId={pool?.id}
        indexRoute="api.v1.admin.candidate-clusters.index"
        destroyRoute="api.v1.admin.candidate-clusters.destroy"
        relation="candidate"
        addable={false}
        columns={[
            { key: 'full_name', label: 'Candidate' },
            { key: 'email', label: 'Email' },
            { key: 'distance', label: 'Distance', width: '120px' },
        ]}
        emptyTitle="No members"
        emptyBody="The nightly rebuild fills this in once enough candidates have an embedding."
        confirmBody="Remove this candidate from the pool? The next rebuild may put them back."
        searchPlaceholder="Search candidates…"
        cells={membershipCells}
        banner={rebuildBanner}
    />

    <PivotDrawer
        bind:open={postingsOpen}
        title={`Postings in ${pool?.name ?? ''}`}
        width="w-[560px]"
        parentId={pool?.id}
        indexRoute="api.v1.admin.job-offer-clusters.index"
        destroyRoute="api.v1.admin.job-offer-clusters.destroy"
        relation="job_offer"
        addable={false}
        columns={[
            { key: 'name', label: 'Posting' },
            { key: 'slug', label: 'Slug' },
            { key: 'distance', label: 'Distance', width: '120px' },
        ]}
        emptyTitle="No postings"
        emptyBody="Postings are placed by cosine against this pool's centroid on the nightly rebuild."
        confirmBody="Remove this posting from the pool? The next rebuild may put it back."
        searchPlaceholder="Search postings…"
        cells={membershipCells}
        banner={rebuildBanner}
    />
</AdminLayout>

<!-- The single most important thing on either drawer, so it sits above the
     table rather than under it where it would be scrolled past. -->
{#snippet rebuildBanner()}
    <Alert variant="warning">
        <strong>Membership is rebuilt nightly.</strong>
        RebuildClusters clears every membership row before reassigning, so a removal here lasts
        until the next run and may be undone by it.
    </Alert>
{/snippet}

{#snippet membershipCells(row, column)}
    {#if column.key === 'distance'}
        <!-- Null distance means nothing measured this row against the centroid. -->
        {#if row.distance === null || row.distance === undefined}
            <span class="text-xs text-muted-foreground">Added by hand</span>
        {:else}
            <span class="font-mono text-xs">{Number(row.distance).toFixed(2)}</span>
        {/if}
    {:else}
        <!-- One snippet, both drawers: a row carries either a candidate or a
             posting, so read whichever is there before the pivot's own columns. -->
        {row.candidate?.[column.key] ?? row.job_offer?.[column.key] ?? row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search pools…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <!-- No Add button: pools are discovered by the nightly rebuild. -->
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form(ready)}
    <ClusterForm cluster={editing} {ready} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    {#if isEmpty && canCountCandidates && embeddedTotal !== null}
        <div class="px-5 pt-4">
            <Alert variant="info">
                <strong>
                    {embeddedTotal} of {MIN_CANDIDATES} candidates {embeddedTotal === 1 ? 'has' : 'have'} an embedding.
                </strong>
                Pools appear once {MIN_CANDIDATES} do. Until then, matching falls back to every open posting.
            </Alert>
        </div>
    {/if}

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
        emptyIcon="ki-filled ki-abstract-26"
        emptyTitle="No talent pools yet"
        emptyBody="Pools are discovered, not created. A nightly job clusters candidates by their CV once at least 30 of them have an embedding — until then, matching falls back to every open posting."
        actionsWidth="140px"
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex min-w-0 flex-col">
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="220px" title={row.name} />
            </span>
            {#if row.description}
                <span class="text-xs text-muted-foreground">
                    <ClampText value={row.description} maxWidth="220px" title={row.description} />
                </span>
            {:else}
                <span class="text-xs text-muted-foreground">No description yet</span>
            {/if}
        </div>
    {:else if column.key === 'candidates_count'}
        <Badge variant={row.candidates_count ? 'primary' : 'secondary'}>{row.candidates_count ?? 0}</Badge>
    {:else if column.key === 'job_offers_count'}
        <Badge variant={row.job_offers_count ? 'info' : 'secondary'}>{row.job_offers_count ?? 0}</Badge>
    {:else if column.key === 'is_locked'}
        <Badge variant={row.is_locked ? 'primary' : 'secondary'}>{row.is_locked ? 'Locked' : 'Auto'}</Badge>
    {:else if column.key === 'rebuilt_at'}
        {#if row.rebuilt_at}
            <span class="flex items-center gap-1.5">
                <DateTime value={row.rebuilt_at} />
                <!-- A pool the maths did not reproduce keeps its row but loses its
                     centroid, and can no longer place a posting. -->
                {#if !row.has_centroid}<Badge variant="warning">No centroid</Badge>{/if}
            </span>
        {:else}
            <span class="text-muted-foreground">Never</span>
        {/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('clusters.update') && { icon: 'ki-pencil', label: 'Rename', onclick: () => edit(row) },
        hasPermission('candidate-clusters.index') && { icon: 'ki-people', label: 'Candidates', onclick: () => showMembers(row) },
        hasPermission('job-offer-clusters.index') && { icon: 'ki-briefcase', label: 'Postings', onclick: () => showPostings(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('clusters.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
