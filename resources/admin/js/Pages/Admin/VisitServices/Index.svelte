<script>
    /**
     * Visit services index — the bookable visits, with a publish workflow and
     * per-locale copy.
     *
     * Two clocks, like job offers: `status` is editorial (is the visit offered at
     * all), while whether anyone can BOOK is derived from its time slots. The Times
     * column says how many are still open, because a published visit with none is
     * the one failure this page exists to make visible.
     */
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
    import VisitServiceForm from './VisitServiceForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { VISIT_SERVICE_STATUS_LABELS, VISIT_SERVICE_STATUS_VARIANTS } from '@/lib/visitService';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { router } from '@inertiajs/svelte';

    const list = useIndex('api.v1.admin.visit-services.index', { perPage: 15, sort: 'order' });

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
        { key: 'duration_minutes', label: 'Duration', sortable: true, truncate: false, width: '110px' },
        { key: 'slots', label: 'Times', truncate: false, width: '140px' },
        { key: 'reservations_count', label: 'Bookings', truncate: false, width: '110px' },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'status', label: 'Status' },
        { value: 'duration_minutes', label: 'Duration' },
        { value: 'published_at', label: 'Published' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(VISIT_SERVICE_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (o) => { editing = o; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (o) => { viewing = o; viewOpen = true; };
    const showActivity = (o) => { activityRow = o; activityOpen = true; };

    // The calendar and the queue are pages, not drawers: a month of times needs the
    // room, and a filtered queue has to stay linkable and exportable.
    const openSlots = (o) => router.visit(route('web.admin.visit-slots.index', { 'filter[visit_service_id]': o.id }));
    const openReservations = (o) =>
        router.visit(route('web.admin.visit-reservations.index', { 'filter[visit_service_id]': o.id }));

    async function remove(o) {
        if (!(await confirm({
            body: `Delete ${o.name}? Its time slots go with it. Any reservation against them would go too, so this is refused while the visit has active bookings — hide it instead. Uploaded images return to the media library.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.visit-services.destroy', o.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Visit services</title></svelte:head>

<AdminLayout title="Visit services">
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
        title="Visit service"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: VISIT_SERVICE_STATUS_LABELS[viewing.status] ?? viewing.status, variant: VISIT_SERVICE_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={[
            { label: 'Slug', value: viewing?.slug },
            { label: 'Duration', value: viewing?.duration_minutes ? `${viewing.duration_minutes} min` : '' },
            { label: 'Max visitors', value: viewing?.max_visitors ?? '' },
            { label: 'Order', value: viewing?.order ?? '' },
            { label: 'Time slots', value: viewing?.slots_count ?? '' },
            { label: 'Open times', value: viewing?.open_slots_count ?? '' },
            { label: 'Bookings', value: viewing?.reservations_count ?? '' },
            { label: 'Published at', value: viewing?.published_at ?? '' },
            { label: 'Stylesheet', value: viewing?.css_url || '' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="visit_service" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search visits…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('visit-services.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add visit
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
    <VisitServiceForm visitService={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No visits yet"
        emptyBody="Add a visit, then give it some times."
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
                <ClampText value={row.name} maxWidth="220px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'duration_minutes'}
        <span class="text-sm">{row.duration_minutes} min</span>
    {:else if column.key === 'slots'}
        <!-- Open times out of all times. A PUBLISHED visit with none is the state
             worth spotting from the list, so it says so rather than showing a 0. -->
        {#if row.slots_count}
            <span class="flex items-center gap-1.5 text-sm">
                <span class="text-mono">{row.open_slots_count ?? 0}</span>
                <span class="text-muted-foreground">of {row.slots_count}</span>
                {#if row.status === 'published' && !row.open_slots_count}
                    <Badge variant="warning">None open</Badge>
                {/if}
            </span>
        {:else if row.status === 'published'}
            <Badge variant="warning">No times</Badge>
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'reservations_count'}
        <span class="text-sm">{row.reservations_count ?? 0}</span>
    {:else if column.key === 'status'}
        <Badge variant={VISIT_SERVICE_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {VISIT_SERVICE_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('visit-services.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('visit-slots.index') && { icon: 'ki-calendar', label: 'Time slots', onclick: () => openSlots(row) },
        hasPermission('visit-reservations.index') && { icon: 'ki-people', label: 'Bookings', onclick: () => openReservations(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('visit-services.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
