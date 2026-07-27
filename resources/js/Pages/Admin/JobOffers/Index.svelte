<script>
    /**
     * Job offers index — vacancies with a publish workflow and per-locale copy.
     *
     * Two clocks: `status` is editorial (is the page live), `deadline_at` is
     * operational (are applications still accepted). Nothing flips one from the
     * other, so the Deadline cell marks a passed date itself.
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
    import JobOfferForm from './JobOfferForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        EDUCATION_LEVEL_LABELS,
        EMPLOYMENT_TYPE_LABELS,
        JOB_OFFER_STATUS_LABELS,
        JOB_OFFER_STATUS_VARIANTS,
        WORK_MODE_LABELS,
        deadlinePassed,
        experienceLabel,
    } from '@/lib/jobOffer';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.job-offers.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const categoryId = $derived(list.params.filter?.category_id ?? null);

    // Deep link: the loaded rows already carry the category, so the drawer can show
    // its NAME straight away. Select resolves the label itself when no row matched.
    const filteredCategory = $derived(
        categoryId ? (list.rows.find((r) => r.category?.id === categoryId)?.category ?? null) : null,
    );

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'employment_type', label: 'Employment', truncate: false },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
        { key: 'deadline_at', label: 'Deadline', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'status', label: 'Status' },
        { value: 'published_at', label: 'Published' },
        { value: 'deadline_at', label: 'Deadline' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(JOB_OFFER_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'employment_type',
            type: 'select',
            label: 'Employment type',
            options: Object.entries(EMPLOYMENT_TYPE_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'work_mode',
            type: 'select',
            label: 'Work mode',
            options: Object.entries(WORK_MODE_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'category_id',
            type: 'resource-select',
            label: 'Category',
            resource: 'api.v1.admin.categories.index',
            placeholder: 'All categories',
            initialOptions: () => (filteredCategory ? [{ value: filteredCategory.id, label: filteredCategory.name }] : []),
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (o) => { editing = o; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (o) => { viewing = o; viewOpen = true; };
    const showActivity = (o) => { activityRow = o; activityOpen = true; };

    async function remove(o) {
        if (!(await confirm({
            body: `Delete ${o.name}? Its uploaded images are returned to the media library, not destroyed. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.job-offers.destroy', o.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Job offers</title></svelte:head>

<AdminLayout title="Job offers">
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
        title="Job offer"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: JOB_OFFER_STATUS_LABELS[viewing.status] ?? viewing.status, variant: JOB_OFFER_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={[
            { label: 'Slug', value: viewing?.slug },
            { label: 'Category', value: viewing?.category?.name || '—' },
            { label: 'Employment type', value: EMPLOYMENT_TYPE_LABELS[viewing?.employment_type] ?? viewing?.employment_type ?? '—' },
            { label: 'Work mode', value: WORK_MODE_LABELS[viewing?.work_mode] ?? viewing?.work_mode ?? '—' },
            { label: 'Experience', value: experienceLabel(viewing?.experience_years) },
            { label: 'Education', value: EDUCATION_LEVEL_LABELS[viewing?.education_level] ?? 'Unspecified' },
            { label: 'Start date', date: viewing?.start_date },
            { label: 'Deadline', date: viewing?.deadline_at },
            { label: 'Applications', value: viewing?.is_open ? 'Open' : 'Closed' },
            { label: 'Published at', value: viewing?.published_at ?? '—' },
            { label: 'Stylesheet', value: viewing?.css_url || '—' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="job_offer" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search job offers…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('job-offers.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add job offer
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
    <JobOfferForm jobOffer={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No job offers yet"
        emptyBody="Create a job offer to get started."
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
    {:else if column.key === 'employment_type'}
        <Badge variant="info">
            {EMPLOYMENT_TYPE_LABELS[row.employment_type] ?? row.employment_type}
        </Badge>
    {:else if column.key === 'status'}
        <Badge variant={JOB_OFFER_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {JOB_OFFER_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'deadline_at'}
        {#if row.deadline_at}
            <!-- A passed deadline never changes the status, so say so here. -->
            <span class="flex items-center gap-1.5 {deadlinePassed(row.deadline_at) ? 'text-destructive' : ''}">
                <DateTime value={row.deadline_at} />
                {#if deadlinePassed(row.deadline_at)}
                    <Badge variant="destructive">Closed</Badge>
                {/if}
            </span>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('job-offers.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('job-offers.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
