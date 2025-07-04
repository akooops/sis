<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

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
    let formData = {
        starts_at: '',
        ends_at: '',
        capacity: 10
    };

    // Form errors
    let errors = {};

    function openDrawer(date = null, endDate = null) {
        if (date) {
            const startDate = new Date(date);
            let endDateTime;
            
            if (endDate) {
                // User selected a range
                endDateTime = new Date(endDate);
            } else {
                // Single date selected, default to 2 hours later
                endDateTime = new Date(startDate.getTime() + (2 * 60 * 60 * 1000));
            }
            
            // Format dates for Flatpickr (Y-m-d H:i format)
            const formatDateForFlatpickr = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}`;
            };
            
            formData.starts_at = formatDateForFlatpickr(startDate);
            formData.ends_at = formatDateForFlatpickr(endDateTime);
        } else {
            formData = {
                starts_at: '',
                ends_at: '',
                capacity: 10
            };
        }
        
        // Simulate clicking the toggle button
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#time_slot_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    async function handleSubmit() {
        try {
            // Clear previous errors
            errors = {};

            const response = await fetch(route('admin.visit-time-slots.store', visitService.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: "Time slot created successfully!",
                    variant: "success",
                    position: "bottom-right",
                });

                // Simulate clicking the dismiss button
                const dismissButton = document.querySelector('#cancel-button');
                if (dismissButton) {
                    dismissButton.click();
                }
                
                // Reset form data
                formData = {
                    starts_at: '',
                    ends_at: '',
                    capacity: 10
                };
                
                // Clear errors
                errors = {};
                
                // Refresh calendar data with current visible date range
                if (window.calendar) {
                    const currentView = window.calendar.view;
                    const startDate = currentView.currentStart.toISOString().split('T')[0];
                    const endDate = currentView.currentEnd.toISOString().split('T')[0];
                    fetchTimeSlots(startDate, endDate);
                }
            } else {
                // Handle error response
                const errorData = await response.json();
                errors = errorData.errors || {};
                
                // Show error toast
                const errorMessage = errorData.message || 'Error creating time slot. Please try again.';
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: errorMessage,
                    variant: "destructive",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error creating time slot:', error);
            
            // Show network error toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Network error. Please check your connection and try again.",
                variant: "destructive",
                position: "bottom-right",
            });
        }
    }

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
                // Open drawer with selected date range
                openDrawer(info.start, info.end);
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
                    <button on:click={() => openDrawer()} class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus text-base"></i>
                        Add Time Slot
                    </button>

                    <button style="display:none" data-kt-drawer-toggle="#time_slot_drawer"></button>
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

    <!-- Time Slot Drawer -->
    <div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="time_slot_drawer">
        <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
            Add Time Slot
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        
        <div class="p-5">
            <form on:submit|preventDefault={handleSubmit} class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-mono mb-2">Start Time</label>
                    <Flatpickr
                        bind:value={formData.starts_at}
                        placeholder="Select start date and time"
                        config={{
                            enableTime: true,
                            dateFormat: 'Y-m-d H:i',
                            time_24hr: false,
                            minDate: 'today'
                        }}
                    />
                    {#if errors.starts_at}
                        <p class="text-sm text-destructive mt-1">{errors.starts_at}</p>
                    {/if}
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-mono mb-2">End Time</label>
                    <Flatpickr
                        bind:value={formData.ends_at}
                        placeholder="Select end date and time"
                        config={{
                            enableTime: true,
                            dateFormat: 'Y-m-d H:i',
                            time_24hr: false,
                            minDate: formData.starts_at || 'today'
                        }}
                    />
                    {#if errors.ends_at}
                        <p class="text-sm text-destructive mt-1">{errors.ends_at}</p>
                    {/if}
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-mono mb-2">Capacity</label>
                    <input 
                        type="number" 
                        bind:value={formData.capacity}
                        min="1"
                        class="kt-input w-full {errors.capacity ? 'kt-input-error' : ''}"
                        required
                    />
                    {#if errors.capacity}
                        <p class="text-sm text-destructive mt-1">{errors.capacity}</p>
                    {/if}
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button id="cancel-button" type="button" class="kt-btn kt-btn-outline flex-1" data-kt-drawer-dismiss="true">
                        Cancel
                    </button>
                    <button type="submit" class="kt-btn kt-btn-primary flex-1">
                        Create Time Slot
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>