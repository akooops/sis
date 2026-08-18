<script>
    /**
     * Time slots — A CALENDAR, NOT A TABLE, and the only page in the admin that is.
     *
     * A month of tours is thirty or ninety rows whose only meaningful ordering is
     * the one a calendar draws, and the question an admin actually has ("is
     * anything on next Tuesday?") is answered by looking rather than by filtering.
     * So there is no pagination here at all: the visible month IS the query, and it
     * is fetched whole from api.v1.admin.visit-slots.calendar each time the view
     * moves. That endpoint is capped by a window rather than a page size.
     *
     * FullCalendar is loaded through a dynamic import, so its ~250 kB is a chunk
     * only this page downloads.
     *
     * ONE VISIT AT A TIME. Slots belong to a visit and their limits mean nothing
     * pooled together, so the picker is required rather than being an "all" filter.
     * It reads ?filter[visit_service_id]= so the Time slots action on the services
     * list lands here already pointed at one.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import SlotDrawer from './SlotDrawer.svelte';
    import BulkSlotDrawer from './BulkSlotDrawer.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { hasPermission } from '@/lib/permissions';
    import { SLOT_STATE_CLASSES, SLOT_STATE_LABELS } from '@/lib/visitService';

    /** The service the page opened on, from the deep link. */
    const initialServiceId = new URLSearchParams(window.location.search).get('filter[visit_service_id]');

    let serviceId = $state(initialServiceId);
    let service = $state(null);
    let services = $state([]);

    let host = $state(null);
    let calendar = null;
    let loading = $state(false);
    let error = $state('');

    let slotOpen = $state(false);
    let editingSlot = $state(null);
    let draggedRange = $state(null);
    let bulkOpen = $state(false);

    const canWrite = hasPermission('visit-slots.store');

    /*
     * The picker's own options. Loaded once rather than through a resource-select,
     * because the page also needs the CHOSEN service's duration and default limit
     * to pre-fill the drawers, and a remote select hands back only an id.
     */
    $effect(() => {
        api.get(route('api.v1.admin.visit-services.index'), { per_page: 100, sort: 'order' })
            .then((d) => {
                services = d?.data ?? [];

                if (!serviceId && services.length) serviceId = services[0].id;
            })
            .catch(() => {});
    });

    $effect(() => {
        service = services.find((s) => s.id === serviceId) ?? null;
    });

    const serviceOptions = $derived(services.map((s) => ({ value: s.id, label: s.name })));

    /** Build the calendar once its host exists. */
    $effect(() => {
        if (!host || calendar) return;

        let live = true;

        import('@/lib/calendar').then(({ createCalendar }) => {
            if (!live) return;

            calendar = createCalendar(host, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                slotMinTime: '06:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                // Drag a range to create. Only where the admin may actually create
                // one, so a read-only user is not offered a drawer that 403s.
                selectable: canWrite,
                select: (info) => {
                    if (!serviceId) return;

                    editingSlot = null;
                    draggedRange = { start: info.start, end: info.end };
                    slotOpen = true;
                    calendar?.unselect();
                },
                eventClick: (info) => {
                    editingSlot = info.event.extendedProps.slot;
                    draggedRange = null;
                    slotOpen = true;
                },
                events: (info, success, failure) => load(info, success, failure),
            });
        });

        return () => {
            live = false;
        };
    });

    /*
     * Refetch when the visit changes. `calendar` is read so the effect waits for it
     * to exist, and refetchEvents re-runs the feed below with the CURRENT
     * serviceId — which is why the feed reads it rather than closing over it.
     */
    $effect(() => {
        const id = serviceId;

        if (calendar && id) calendar.refetchEvents();
    });

    /**
     * FullCalendar's feed function: it hands over the visible window and takes the
     * events back, so navigating months needs no listener of its own — one request
     * per view change, and none for anything else.
     */
    async function load(info, success, failure) {
        if (!serviceId) {
            success([]);

            return;
        }

        loading = true;
        error = '';

        try {
            const rows = await api.get(route('api.v1.admin.visit-slots.calendar'), {
                visit_service_id: serviceId,
                from: info.startStr,
                to: info.endStr,
            });

            success((rows ?? []).map(toEvent));
        } catch (cause) {
            error = cause?.message ?? 'The time slots could not be loaded.';
            failure?.(cause);
        } finally {
            loading = false;
        }
    }

    function toEvent(slot) {
        const classNames = [SLOT_STATE_CLASSES[slot.state] ?? 'is-closed'];

        if (slot.is_overbooked) classNames.push('is-overbooked');

        return {
            id: slot.id,
            start: slot.start ?? slot.starts_at,
            end: slot.end ?? slot.ends_at,
            title: `${slot.reserved}/${slot.capacity}`,
            classNames,
            // The whole row travels with the event, so clicking one opens the
            // drawer on data already fetched rather than on a second request.
            extendedProps: { slot },
        };
    }

    const refresh = () => calendar?.refetchEvents();

    function addSlot() {
        if (!serviceId) {
            toast.error('Pick a visit first.');

            return;
        }

        editingSlot = null;
        draggedRange = null;
        slotOpen = true;
    }
</script>

<svelte:head><title>Saud International Schools — Visit time slots</title></svelte:head>

<AdminLayout title="Visit time slots" breadcrumbs={[{ label: 'Visits' }, { label: 'Time slots' }]}>
    <Card>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-[260px]">
                    <Select
                        options={serviceOptions}
                        bind:value={serviceId}
                        clearable={false}
                        placeholder="Pick a visit…"
                    />
                </div>

                {#if loading}
                    <Spinner />
                {/if}
            </div>

            <div class="flex items-center gap-2">
                {#if service}
                    <span class="text-sm text-muted-foreground">
                        {service.duration_minutes} min · up to {service.max_visitors} visitors
                    </span>
                {/if}

                {#if canWrite}
                    <Button variant="outline" size="sm" onclick={() => (bulkOpen = true)} disabled={!serviceId}>
                        <i class="ki-filled ki-calendar-add"></i>Generate
                    </Button>
                    <Button variant="primary" size="sm" onclick={addSlot} disabled={!serviceId}>
                        <i class="ki-filled ki-plus"></i>Add time
                    </Button>
                {/if}
            </div>
        </div>

        <div class="p-5">
            {#if error}
                <Alert variant="danger" class="mb-4">{error}</Alert>
            {/if}

            {#if services.length === 0}
                <EmptyState
                    icon="ki-filled ki-calendar"
                    title="No visits yet"
                    body="Add a visit before giving it times."
                />
            {:else}
                <div class="mb-4 flex flex-wrap items-center gap-4 text-sm">
                    {#each Object.entries(SLOT_STATE_LABELS) as [state, label] (state)}
                        <span class="flex items-center gap-2">
                            <span class="size-3 rounded-sm fc-legend-{SLOT_STATE_CLASSES[state]}"></span>
                            <span class="text-muted-foreground">{label}</span>
                        </span>
                    {/each}
                    {#if canWrite}
                        <span class="text-muted-foreground">· Drag a range to add a time, click one to edit it.</span>
                    {/if}
                </div>

                <div bind:this={host}></div>
            {/if}
        </div>
    </Card>

    <SlotDrawer
        bind:open={slotOpen}
        {serviceId}
        visitSlot={editingSlot}
        range={draggedRange}
        defaultCapacity={service?.max_visitors ?? 1}
        onsaved={refresh}
    />
    <BulkSlotDrawer
        bind:open={bulkOpen}
        {serviceId}
        defaultMinutes={service?.duration_minutes ?? 60}
        onsaved={refresh}
    />
</AdminLayout>
