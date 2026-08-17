<script>
    /**
     * Job applications — the HR queue.
     *
     * ITS OWN PAGE, not a drawer off Job offers. The offers table drills in with
     * `?filter[job_offer_id]=…` and useIndex reads that out of the URL at init, so
     * the deep link, the filter drawer and the export are all the same state — a
     * drawer would have made a filtered queue unlinkable and unexportable. The
     * job notifications link here the same way, with `?filter[id]=…`.
     *
     * READ-ONLY EXCEPT THE FIVE TRANSITIONS. There is no create and no edit: an
     * application is the record of something a person did, and the only thing an
     * admin changes is where it has reached. Each transition is its own
     * permission, so a screener who may shortlist but not reject sees exactly the
     * moves they hold.
     *
     * THE MATCH COLUMN IS THE ONE TO GET RIGHT. A null score means the scoring
     * queue has not reached this pair — NOT that the model rated them nothing. It
     * renders as a neutral "Not scored yet", never as 0 and never in red. With no
     * AI provider configured, that is currently every row.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import ServerExportButton from '@/components/data/ServerExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import ApplicationDrawer from './ApplicationDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        JOB_APPLICATION_STATUS_LABELS,
        JOB_APPLICATION_STATUS_VARIANTS,
        availableTransitions,
    } from '@/lib/jobApplication';
    import { NOT_SCORED, isScored, scoreVariant } from '@/lib/candidate';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { router } from '@inertiajs/svelte';

    const list = useIndex('api.v1.admin.job-applications.index', { perPage: 15, sort: '-applied_at' });

    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const jobOfferId = $derived(list.params.filter?.job_offer_id ?? null);

    // Deep link from the offers page: the loaded rows already carry the posting's
    // name, so the filter drawer shows it straight away. Select resolves the label
    // itself when no row matched — a posting with no applications has nothing here
    // to read it from.
    const filteredOffer = $derived(
        jobOfferId
            ? (list.rows.find((r) => r.job_offer_id === jobOfferId)?.job_offer_name ?? null)
            : null,
    );

    const canExport = $derived(hasPermission('job-applications.export'));

    // The current query, minus paging: an export is never one page.
    const exportParams = $derived({
        filter: { ...(list.params.filter ?? {}) },
        sort: list.params.sort || undefined,
    });

    // The Posting column is dropped once one posting is filtered — it would
    // repeat the same name down the whole table.
    const columns = $derived(
        [
            { key: 'id', label: 'ID', sortable: true, width: '110px', truncate: false },
            { key: 'candidate', label: 'Applicant', truncate: false },
            jobOfferId ? null : { key: 'job_offer', label: 'Posting', truncate: false },
            { key: 'status', label: 'Status', sortable: true, truncate: false },
            { key: 'score', label: 'Match', truncate: false, width: '150px' },
            { key: 'applied_at', label: 'Applied', sortable: true, truncate: false },
        ].filter(Boolean),
    );

    /*
     * Mirrors the controller's allowedSorts.
     *
     * NO "Match" option: the score lives on candidate_matches, and sorting by it
     * would need a join that makes `id` ambiguous and breaks the export's keyset
     * walk. Rank by score in the posting's Matches drawer instead, where the score
     * is a real column on the table being read.
     */
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'status', label: 'Status' },
        { value: 'applied_at', label: 'Applied' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'job_offer_id',
            type: 'resource-select',
            label: 'Posting',
            resource: 'api.v1.admin.job-offers.index',
            placeholder: 'All postings',
            initialOptions: () => (filteredOffer ? [{ value: jobOfferId, label: filteredOffer }] : []),
        },
        {
            key: 'candidate_id',
            type: 'resource-select',
            label: 'Candidate',
            resource: 'api.v1.admin.candidates.index',
            labelKey: 'full_name',
            placeholder: 'All candidates',
        },
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(JOB_APPLICATION_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        { key: 'applied', type: 'daterange', label: 'Applied' },
        // "Has the scorer reached this one yet?" — the only score question that is
        // answerable honestly while no provider is configured.
        { key: 'unscored', type: 'boolean', label: 'Not scored yet' },
    ];

    const view = (a) => { viewing = a; viewOpen = true; };
    const showActivity = (a) => { activityRow = a; activityOpen = true; };

    /**
     * Move an application along.
     *
     * The server is the enforcement: a disallowed move answers 422 with the
     * reason, and that message is what gets shown rather than anything guessed
     * client-side. The menu only hides doors the state machine has locked.
     */
    async function transition(row, action) {
        try {
            await api.post(route(`api.v1.admin.job-applications.${action}`, row.id));
            toast.success('Updated successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(row) {
        if (!(await confirm({
            body: `Delete the application from ${row.candidate_name ?? 'this applicant'}? The candidate and their profile stay; only this application to ${row.job_offer_name ?? 'this posting'} goes. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.job-applications.destroy', row.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    const openCandidate = (row) =>
        router.visit(route('web.admin.candidates.index', { 'filter[id]': row.candidate_id }));
</script>

<svelte:head><title>Saud International Schools — Job applications</title></svelte:head>

<AdminLayout title="Job applications">
    <IndexCard showForm={false} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ApplicationDrawer bind:open={viewOpen} application={viewing} onchanged={() => list.refresh()} />
    <ActivityDrawer bind:open={activityOpen} subjectType="job_application" subjectId={activityRow?.id} title={activityRow?.candidate_name} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search by applicant or ID…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
    </div>
    {#if canExport}
        <!-- Never disabled, unlike the submissions export: an application's
             columns are fixed, so a cross-posting export is meaningful and no
             filter is required first. -->
        <ServerExportButton
            routeName="api.v1.admin.job-applications.export"
            params={exportParams}
            fallbackFilename="job-applications.csv"
            label="Export CSV"
            title="Download every application matching these filters."
        />
    {/if}
{/snippet}

<!-- Read-only module: nothing creates or edits an application. -->
{#snippet form()}{/snippet}

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
        emptyIcon="ki-filled ki-briefcase"
        emptyTitle="No applications yet"
        emptyBody="Applications arrive from the public job pages and appear here."
        actionsWidth="110px"
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'candidate'}
        <div class="flex min-w-0 flex-col">
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.candidate_name ?? '—'} maxWidth="200px" title={row.candidate_name} />
            </span>
            <span class="text-xs text-muted-foreground">
                <ClampText value={row.candidate_email ?? ''} maxWidth="200px" title={row.candidate_email} />
            </span>
        </div>
    {:else if column.key === 'job_offer'}
        <ClampText value={row.job_offer_name ?? '—'} maxWidth="200px" title={row.job_offer_name} />
    {:else if column.key === 'status'}
        <Badge variant={JOB_APPLICATION_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {JOB_APPLICATION_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'score'}
        <!-- Unscored is NOT zero and never reads as a poor fit. -->
        {#if isScored(row.score)}
            <span class="flex items-center gap-1.5">
                <Badge variant={scoreVariant(row.score)}>{row.score}</Badge>
                {#if row.score >= 60}<Badge variant="success" outline={false}>Strong</Badge>{/if}
            </span>
        {:else}
            <Badge variant="secondary">{NOT_SCORED}</Badge>
        {/if}
    {:else if column.key === 'applied_at'}
        {#if row.applied_at}
            <DateTime value={row.applied_at} />
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        // Only the moves the state machine allows AND this admin holds. A hired
        // application offers none — there is no route back out of it.
        ...availableTransitions(row.status)
            .filter((t) => hasPermission(t.permission))
            .map((t) => ({ icon: t.icon, label: t.label, variant: t.variant, onclick: () => transition(row, t.action) })),
        hasPermission('candidates.index') && { icon: 'ki-profile-circle', label: 'Candidate', onclick: () => openCandidate(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('job-applications.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
