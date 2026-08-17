<script>
    /**
     * Candidates — the PEOPLE, as distinct from their applications.
     *
     * The split is what lets the same human apply to three postings without
     * becoming three records, and it is what makes "this candidate's other good
     * matches" and "this posting's good candidates" the same query read from two
     * ends.
     *
     * NO CREATE BUTTON. ApplicationProjector is the only thing that makes a
     * candidate — someone exists in here because they applied — and there is no
     * `candidates.store` permission to gate a create with. Edit exists for contact
     * details only; the form says what it fights with.
     *
     * `has_cv` is filterable even though the CV is required at submit, because
     * `cv_media_id` is nullOnDelete and the projector only sets it when the media
     * resolves. A candidate with no CV is an anomaly worth being able to find.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import CandidateDrawer from './CandidateDrawer.svelte';
    import CandidateForm from './CandidateForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { router } from '@inertiajs/svelte';

    const list = useIndex('api.v1.admin.candidates.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '110px', truncate: false },
        { key: 'full_name', label: 'Candidate', truncate: false },
        { key: 'phone', label: 'Phone', truncate: false },
        { key: 'country_name', label: 'Nationality', truncate: false },
        { key: 'skills', label: 'Skills', truncate: false, maxWidth: '240px' },
        { key: 'applications_count', label: 'Applications', align: 'center', width: '120px', truncate: false },
        { key: 'created_at', label: 'Added', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'first_name', label: 'First name' },
        { value: 'last_name', label: 'Last name' },
        { value: 'email', label: 'Email' },
        { value: 'created_at', label: 'Added' },
        { value: 'summarised_at', label: 'Summarised' },
        { value: 'embedded_at', label: 'Embedded' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'country_id',
            type: 'resource-select',
            label: 'Nationality',
            resource: 'api.v1.admin.countries.index',
            placeholder: 'All countries',
        },
        {
            key: 'cluster_id',
            type: 'resource-select',
            label: 'Talent pool',
            resource: 'api.v1.admin.clusters.index',
            placeholder: 'All pools',
        },
        { key: 'skill', type: 'text', label: 'Skill' },
        { key: 'has_cv', type: 'boolean', label: 'Has a CV' },
        // The column that explains an empty Talent Pools page: clustering needs
        // at least 30 embedded candidates before it runs at all.
        { key: 'embedded', type: 'boolean', label: 'Has an embedding' },
        { key: 'created', type: 'daterange', label: 'Added' },
    ];

    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };

    const openApplications = (row) =>
        router.visit(route('web.admin.job-applications.index', { 'filter[candidate_id]': row.id }));

    async function remove(row) {
        if (!(await confirm({
            // Every one of these cascades; the CV does not, because it belongs to
            // the form submission that carried it. Both halves are worth saying.
            body: `Delete ${row.full_name}? Their applications, match scores and pool memberships go with them. Their CV file stays in the form submission that carried it. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.candidates.destroy', row.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Candidates</title></svelte:head>

<AdminLayout title="Candidates">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <CandidateDrawer bind:open={viewOpen} candidate={viewing} />
    <ActivityDrawer bind:open={activityOpen} subjectType="candidate" subjectId={activityRow?.id} title={activityRow?.full_name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search by name, email or phone…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <!-- No Add button: a candidate exists because they applied. -->
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form(ready)}
    <CandidateForm candidate={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyIcon="ki-filled ki-people"
        emptyTitle="No candidates yet"
        emptyBody="A candidate is created the first time someone applies through a job page."
        actionsWidth="110px"
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'full_name'}
        <div class="flex items-center gap-3">
            <Avatar name={row.full_name} size="sm" />
            <div class="flex min-w-0 flex-col">
                <span class="text-sm font-medium text-mono">
                    <ClampText value={row.full_name} maxWidth="180px" title={row.full_name} />
                </span>
                <span class="text-xs text-muted-foreground">
                    <ClampText value={row.email} maxWidth="180px" title={row.email} />
                </span>
            </div>
        </div>
    {:else if column.key === 'phone'}
        {row.phone || '—'}
    {:else if column.key === 'country_name'}
        {row.country_name || '—'}
    {:else if column.key === 'skills'}
        {#if row.skills?.length}
            <div class="flex flex-wrap items-center gap-1">
                {#each row.skills.slice(0, 3) as skill (skill.id)}
                    <Badge variant="secondary">{skill.name}</Badge>
                {/each}
                {#if row.skills.length > 3}
                    <span class="text-xs text-muted-foreground">+{row.skills.length - 3}</span>
                {/if}
            </div>
        {:else}
            <span class="text-xs text-muted-foreground">No skills recorded</span>
        {/if}
    {:else if column.key === 'applications_count'}
        <Badge variant={row.applications_count ? 'primary' : 'secondary'}>{row.applications_count ?? 0}</Badge>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('candidates.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('job-applications.index') && { icon: 'ki-briefcase', label: 'Applications', onclick: () => openApplications(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('candidates.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
