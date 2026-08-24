<script>
    /**
     * The admissions desk queue: read-only, plus the six transitions and a note.
     *
     * THERE IS NO CREATE. A booking is the record of something a family did — the
     * submit pipeline and ReservationProjector are the only writers — so IndexCard
     * runs with showForm permanently false and an empty form snippet, the same
     * shape the job applications queue uses.
     *
     * The two date filters answer different questions and both are needed: `Booked`
     * is when the form was submitted, `Visiting on` is when they are actually
     * expected — and the register the desk prints in the morning is the second one.
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
    import ReservationDrawer from './ReservationDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        FACILITY_RESERVATION_STATUS_LABELS,
        FACILITY_RESERVATION_STATUS_VARIANTS,
        availableTransitions,
    } from '@/lib/facilityReservation';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.facility-reservations.index', { perPage: 15, sort: '-booked_at' });

    let viewOpen = $state(false);
    let viewing = $state(null);
    let filtersOpen = $state(false);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const serviceId = $derived(list.params.filter?.facility_id ?? null);

    // Deep link: the loaded rows carry the visit's name, so the drawer can show it
    // straight away rather than as a bare id.
    const filteredService = $derived(
        serviceId ? (list.rows.find((r) => r.facility_id === serviceId)?.service_name ?? null) : null,
    );

    // The Visit column drops when one visit is already filtered — it would repeat
    // the same value down every row.
    const columns = $derived([
        { key: 'id', label: 'ID', sortable: true, width: '110px', truncate: false },
        { key: 'visitor', label: 'Booked by', truncate: false },
        serviceId ? null : { key: 'service_name', label: 'Facility', truncate: false },
        { key: 'slot_starts_at', label: 'Visiting', truncate: false },
        { key: 'visitors_count', label: 'People', sortable: true, truncate: false, width: '90px' },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
        { key: 'booked_at', label: 'Booked', sortable: true, truncate: false },
    ].filter(Boolean));

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'booked_at', label: 'Booked' },
        { value: 'id', label: 'ID' },
        { value: 'status', label: 'Status' },
        { value: 'visitors_count', label: 'People' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'facility_id',
            type: 'resource-select',
            label: 'Facility',
            resource: 'api.v1.admin.facilities.index',
            placeholder: 'All facilities',
            initialOptions: () => (filteredService && serviceId ? [{ value: serviceId, label: filteredService }] : []),
        },
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(FACILITY_RESERVATION_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        // Everyone visiting on one day — the register. A booked_at range cannot
        // answer it: when a booking was MADE has nothing to do with when it IS.
        { key: 'visiting_on', type: 'text', label: 'Visiting on (YYYY-MM-DD)' },
        { key: 'booked', type: 'daterange', label: 'Booked' },
    ];

    const canExport = hasPermission('facility-reservations.export');

    // Page and per_page deliberately omitted: an export is every row the filters
    // match, not the page being looked at.
    const exportParams = $derived({
        filter: { ...(list.params.filter ?? {}) },
        sort: list.params.sort || undefined,
    });

    const view = (r) => { viewing = r; viewOpen = true; };
    const showActivity = (r) => { activityRow = r; activityOpen = true; };

    async function transition(row, action) {
        try {
            await api.post(route(`api.v1.admin.facility-reservations.${action}`, row.id));
            toast.success('Updated successfully.');
            list.refresh();
        } catch (e) {
            // The 422 is the enforcement; its message names the move that was
            // refused and from what.
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(row) {
        if (!(await confirm({
            body: `Delete the booking from ${row.visitor_name ?? 'this visitor'}? The seat is freed and the record is gone. Cancelling frees the seat too, and leaves a trail.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.facility-reservations.destroy', row.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Facility bookings</title></svelte:head>

<AdminLayout title="Facility bookings">
    <IndexCard showForm={false} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReservationDrawer bind:open={viewOpen} reservation={viewing} onchanged={() => list.refresh()} />
    <ActivityDrawer
        bind:open={activityOpen}
        subjectType="facility_reservation"
        subjectId={activityRow?.id}
        title={activityRow?.visitor_name}
    />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search bookings…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
    </div>
    {#if canExport}
        <ServerExportButton
            routeName="api.v1.admin.facility-reservations.export"
            params={exportParams}
            fallbackFilename="facility-reservations.csv"
            label="Export CSV"
            title="Download every booking matching these filters."
        />
    {/if}
{/snippet}

<!-- Nothing to create: the submit pipeline is the only writer. -->
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
        emptyTitle="No bookings yet"
        emptyBody="Bookings made on a facility's reserve page land here."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'visitor'}
        <div class="flex flex-col">
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.visitor_name ?? ''} maxWidth="200px" title={row.visitor_name} />
            </span>
            <span class="text-xs text-muted-foreground">{row.visitor_email ?? ''}</span>
            {#if row.visitor_phone}
                <span class="text-xs text-muted-foreground" dir="ltr">{row.visitor_phone}</span>
            {/if}
        </div>
    {:else if column.key === 'service_name'}
        <ClampText value={row.service_name ?? ''} maxWidth="180px" title={row.service_name} />
    {:else if column.key === 'slot_starts_at'}
        {#if row.slot_starts_at}
            <DateTime value={row.slot_starts_at} />
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'status'}
        <Badge variant={FACILITY_RESERVATION_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {FACILITY_RESERVATION_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'booked_at'}
        {#if row.booked_at}
            <DateTime value={row.booked_at} />
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
        ...availableTransitions(row.status)
            .filter((t) => hasPermission(t.permission))
            .map((t) => ({ icon: t.icon, label: t.label, variant: t.variant, onclick: () => transition(row, t.action) })),
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('facility-reservations.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
