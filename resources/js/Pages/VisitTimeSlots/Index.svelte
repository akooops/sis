<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte'

    // Props from the server
    export let visitService;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Visit Services Management',
            url: route('admin.visit-services.index'),
            active: false
        },
        {
            title: visitService?.name || 'Visit Service',
            url: route('admin.visit-bookings.index', { visitService: visitService?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Visit Time Slots';

    let calendarEl = null;

    function fetchTimeSlots(min, max) {
        fetch(route('admin.visit-time-slots.index', visitService.id) + `?min=${min}&max=${max}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (window.calendar) {
                window.calendar.removeAllEvents();
                data.visitTimeSlots.forEach(slot => {
                    window.calendar.addEvent({
                        title: `(${slot.remaining_capacity}/${slot.capacity}) - ${slot.reserved ? 'Reserved' : 'Available'}`,
                        start: slot.starts_at,
                        end: slot.ends_at,
                        url: route('admin.visit-time-slots.edit', slot.id),
                        backgroundColor: slot.reserved ? '#dc3545' : '#198754'
                    });
                });
            }
        });
    }

    onMount(() => {
        // Initialize FullCalendar
        window.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '20:00:00',
            selectable: true,
            select: function(info) {
                // Navigate to create page with selected date
                window.location.href = route('admin.visit-time-slots.create', visitService.id) + `?date=${info.startStr}`;
            },
            eventClick: function(info) {
                // Navigate to edit page
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            },
            events: [], // Will be loaded via AJAX
            datesSet: function(info) {
                const startDate = info.start.toISOString().split('T')[0];
                const endDate = info.end.toISOString().split('T')[0];
                fetchTimeSlots(startDate, endDate);
            }
        });

        window.calendar.render();
        
        // Load initial data with current week range
        const now = new Date();
        const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
        const endOfWeek = new Date(now.setDate(now.getDate() - now.getDay() + 6));
        
        const startDate = startOfWeek.toISOString().split('T')[0];
        const endDate = endOfWeek.toISOString().split('T')[0];
        fetchTimeSlots(startDate, endDate);
    });

    // Flash message handling
    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
            message: success,
            variant: "success",
            position: "bottom-right",
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>

    <style>
        .fc .fc-timegrid-slot {
            height: 2em;
            border-bottom: 0 !important;
        }

        .fc-daygrid-event {
            background-color: #198754;
            color: #fff;
        }

        .fc-daygrid-event:hover {
            background-color: #198754;
            color: #fff;
        }
    </style>
</svelte:head>


<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Visit Time Slots Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Visit Time Slots</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage time slots for "{visitService?.name}"
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {#if hasPermission('admin.visit-time-slots.store')}
                    <a href="{route('admin.visit-time-slots.create', { visitService: visitService?.id })}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus text-base"></i>
                        Add Time Slot
                    </a>
                    {/if}
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="kt-card">
                <div class="kt-card-content p-0">
                    <div bind:this={calendarEl} id="calendar" style="height: 800px;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout>