<script>
    /**
     * Who fits this posting — the scoring table read from the posting's end.
     *
     * APPLIED VS MERELY RECOMMENDED IS THIS DRAWER'S WHOLE REASON TO EXIST, so it
     * is a first-class column rather than a subtitle. `candidate_matches` holds a
     * row for every pair the scorer evaluated, whether or not anyone applied; the
     * difference is simply whether a `job_applications` row exists for the same
     * pair, which the controller resolves with two correlated subselects. A strong
     * match on a posting someone has NOT applied to is exactly the recommendation
     * HR wants, and it needed no second table to express.
     *
     * SORTED `-score`, and MySQL puts NULL last on DESC — so scored candidates
     * rank first and the ones the queue has not reached fall to the bottom rather
     * than being hidden behind a filter nobody set. With no AI provider
     * configured that is currently every row, and the empty state says so.
     *
     * Reopening for a different posting must refetch, so the filter is applied in
     * an $effect keyed on the posting id and the active tab, wrapped in untrack —
     * the same guard PivotDrawer uses, for the same reason.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Tooltip from '@/components/ui/Tooltip.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { NOT_SCORED, isScored, scoreVariant } from '@/lib/candidate';
    import { JOB_APPLICATION_STATUS_LABELS, JOB_APPLICATION_STATUS_VARIANTS } from '@/lib/jobApplication';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    let { open = $bindable(false), jobOffer = null } = $props();

    let active = $state('all');

    // readUrl:false — it must not fight the page's own query string.
    // immediate:false — a mounted-but-closed drawer must not fetch.
    // pollMs:0 — a drawer is not a live dashboard.
    const list = useIndex('api.v1.admin.candidate-matches.index', {
        perPage: 10,
        sort: '-score',
        readUrl: false,
        immediate: false,
        pollMs: 0,
    });

    $effect(() => {
        if (!open || !jobOffer?.id) return;

        // Both are read so the effect re-runs when either changes.
        const id = jobOffer.id;
        const tab = active;

        untrack(() =>
            list.setFilters({
                job_offer_id: id,
                applied: tab === 'all' ? null : tab === 'applied' ? '1' : '0',
            }),
        );
    });

    const columns = [
        { key: 'candidate', label: 'Candidate', truncate: false },
        { key: 'applied', label: 'Applied', truncate: false, width: '130px' },
        { key: 'score', label: 'Match', sortable: true, truncate: false, width: '150px' },
    ];

    const openCandidate = (row) =>
        router.visit(route('web.admin.candidates.index', { 'filter[id]': row.candidate_id }));

    const openApplication = (row) =>
        router.visit(route('web.admin.job-applications.index', { 'filter[id]': row.application_id }));
</script>

<Drawer bind:open title="Matches" width="w-[640px]">
    {#snippet header()}
        <div class="flex min-w-0 flex-col gap-0.5">
            <h3 class="text-base font-semibold text-mono">
                <ClampText value={jobOffer?.name ?? 'Matches'} />
            </h3>
            <div class="flex items-center gap-2 text-xs font-normal text-muted-foreground">
                <span>Candidates scored against this posting</span>
                <!-- Recommending against a closed posting is worth flagging. -->
                {#if jobOffer && !jobOffer.is_open}
                    <Badge variant="destructive">Closed</Badge>
                {/if}
            </div>
        </div>
    {/snippet}

    <div class="flex flex-col gap-4">
        <Tabs
            tabs={[
                { id: 'all', label: 'All' },
                { id: 'applied', label: 'Applied' },
                { id: 'recommended', label: 'Recommended' },
            ]}
            bind:active
        />

        <SearchBar placeholder="Search candidates…" value={list.search} onsearch={(v) => list.setSearch(v)} />

        <DataTable
            {columns}
            rows={list.rows}
            loading={list.loading}
            meta={list.meta}
            sort={list.params.sort}
            onSort={list.toggleSort}
            onPageChange={(p) => list.goToPage(p)}
            onPerPageChange={(n) => list.setPerPage(n)}
            emptyIcon="ki-filled ki-people"
            emptyTitle="No candidates matched yet"
            emptyBody="Matches are written in the background — after someone applies, and again when the nightly rebuild runs."
            actionsWidth="80px"
            {cells}
            {rowActions}
        />
    </div>
</Drawer>

{#snippet cells(row, column)}
    {#if column.key === 'candidate'}
        <div class="flex min-w-0 flex-col">
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.candidate_name ?? row.candidate_id} maxWidth="200px" title={row.candidate_name} />
            </span>
            <span class="text-xs text-muted-foreground">
                <ClampText value={row.candidate_email ?? ''} maxWidth="200px" title={row.candidate_email} />
            </span>
        </div>
    {:else if column.key === 'applied'}
        {#if row.has_applied}
            <Badge variant={JOB_APPLICATION_STATUS_VARIANTS[row.application_status] ?? 'secondary'}>
                {JOB_APPLICATION_STATUS_LABELS[row.application_status] ?? row.application_status}
            </Badge>
        {:else}
            <Tooltip text="This person has not applied to this posting. The scorer suggested them.">
                <Badge variant="secondary">Recommended</Badge>
            </Tooltip>
        {/if}
    {:else if column.key === 'score'}
        <!-- Never `score ?? 0`: unscored is not a poor fit. -->
        {#if isScored(row.score)}
            <span class="flex items-center gap-1.5">
                <Badge variant={scoreVariant(row.score)}>{row.score}</Badge>
                {#if row.is_strong}<Badge variant="success" outline={false}>Strong</Badge>{/if}
            </span>
        {:else}
            <Badge variant="secondary">{NOT_SCORED}</Badge>
        {/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        hasPermission('candidates.index') && { icon: 'ki-profile-circle', label: 'View candidate', onclick: () => openCandidate(row) },
        row.has_applied && hasPermission('job-applications.index') && { icon: 'ki-briefcase', label: 'Open application', onclick: () => openApplication(row) },
    ].filter(Boolean)} />
{/snippet}
