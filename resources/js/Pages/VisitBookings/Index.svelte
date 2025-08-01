<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import { onMount, tick } from 'svelte';
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
    
    const pageTitle = 'Visit Bookings';

    // Reactive variables
    let visitBookings = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let selectedTimeSlotId = '';
    let selectedTimeSlotText = '';

    // Fetch visit bookings data
    async function fetchVisitBookings() {
        loading = true;
        try {
            const response = await fetch(route('admin.visit-bookings.index', {
                visitService: visitService.id,
                page: currentPage,
                perPage: perPage,
                search: search,
                visit_time_slot_id: selectedTimeSlotId
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            visitBookings = data.visitBookings;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching visit bookings:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search with debouncing
    function handleSearch() {
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for 500ms
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchVisitBookings();
        }, 500);
    }

    // Handle search input change
    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    // Handle time slot selection
    function handleTimeSlotSelect(event) {
        console.log('Time slot selected:', event.detail);
        selectedTimeSlotId = event.detail.value;
        selectedTimeSlotText = event.detail.data.text;
        currentPage = 1;
        fetchVisitBookings();
    }

    // Handle time slot clear
    function handleTimeSlotClear() {
        console.log('Time slot cleared');
        selectedTimeSlotId = '';
        selectedTimeSlotText = '';
        currentPage = 1;
        fetchVisitBookings();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchVisitBookings();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchVisitBookings();
    }

    // Delete visit booking
    async function deleteVisitBooking(visitBookingId) {
        if (!confirm('Are you sure you want to delete this visit booking? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const response = await fetch(route('admin.visit-bookings.destroy', { visitBooking: visitBookingId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.ok) {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: "Visit booking deleted successfully!",
                    variant: "success",
                    position: "bottom-right",
                });

                // Refresh the visit bookings list
                fetchVisitBookings();
            } else {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Error deleting visit booking. Please try again.';
                
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                    message: errorMessage,
                    variant: "error",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error deleting visit booking:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                message: "Network error. Please check your connection and try again.",
                variant: "error",
                position: "bottom-right",
            });
        }
    }

    // Format date time
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    onMount(() => {
        fetchVisitBookings();
    });

    // Flash message handling
    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
            message: success,
            variant: "success",
            position: "bottom-right",
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Visit Bookings Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Visit Bookings Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage visit bookings and appointments for "{visitService?.name}"
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {#if hasPermission('admin.visit-bookings.store')}
                    <a href="{route('admin.visit-bookings.create')}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus text-base"></i>
                        Add New Visit Booking
                    </a>
                    {/if}
                </div>
            </div>

            <!-- Visit Bookings Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="flex items-center gap-3">
                            <div class="kt-input max-w-64 w-64">
                                <i class="ki-filled ki-magnifier"></i>
                                <input 
                                    type="text" 
                                    class="kt-input" 
                                    placeholder="Search visit bookings..." 
                                    bind:value={search}
                                    on:input={handleSearchInput}
                                />
                            </div>
                            {#if selectedTimeSlotId}
                                <!-- Time Slot Badge -->
                                <div class="flex items-center gap-2">
                                    <span class="kt-badge kt-badge-outline kt-badge-primary">
                                        <i class="ki-filled ki-calendar text-sm me-1"></i>
                                        Time Slot: {selectedTimeSlotText}
                                    </span>
                                    <button 
                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                        on:click={handleTimeSlotClear}
                                        title="Clear time slot filter"
                                    >
                                        <i class="ki-filled ki-cross text-sm"></i>
                                    </button>
                                </div>
                            {:else}
                                <!-- Time Slot Filter -->
                                <div class="w-64">
                                    <Select2
                                        id="time-slot-filter"
                                        placeholder="Filter by time slot..."
                                        bind:value={selectedTimeSlotId}
                                        on:select={handleTimeSlotSelect}
                                        on:clear={handleTimeSlotClear}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.visit-time-slots.index', visitService.id),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term,
                                                    perPage: 10
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.visitTimeSlots.map(slot => ({
                                                        id: slot.id,
                                                        text: `${new Date(slot.starts_at).toLocaleString('en-US', {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })} - ${new Date(slot.ends_at).toLocaleString('en-US', {
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}`
                                                    }))
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
                
                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="w-[50px]">
                                        <input class="kt-checkbox kt-checkbox-sm" type="checkbox"/>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">ID</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Guardian</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Time Slot</span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Students count</span>
                                        </span>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    <!-- Loading skeleton rows -->
                                    {#each Array(perPage) as _, i}
                                        <tr>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-4 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex flex-col gap-1">
                                                    <div class="kt-skeleton w-24 h-4 rounded"></div>
                                                    <div class="kt-skeleton w-16 h-3 rounded"></div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-20 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-8 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if !visitBookings || visitBookings.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="8" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-calendar text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No visit bookings found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search || selectedTimeSlotId ? 'No visit bookings match your search criteria.' : 'Get started by creating your first visit booking.'}
                                                </p>
                                                {#if hasPermission('admin.visit-bookings.store')}
                                                <a href="{route('admin.visit-bookings.create')}" class="kt-btn kt-btn-primary">
                                                    <i class="ki-filled ki-plus text-base"></i>
                                                    Create First Visit Booking
                                                </a>
                                                {/if}
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each visitBookings as visitBooking}
                                        <tr class="hover:bg-muted/50">
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={visitBooking.id}/>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">#{visitBooking.id}</span>
                                            </td>
                                            <td>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium text-mono hover:text-primary">
                                                        {visitBooking.guardian_name}
                                                    </span>
                                                    <span class="text-xs text-secondary-foreground">
                                                        {visitBooking.email}
                                                    </span>
                                                    <span class="text-xs text-secondary-foreground">
                                                        {visitBooking.phone}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-sm text-secondary-foreground">
                                                    {formatDateTime(visitBooking.visit_time_slot?.starts_at)} - {formatDateTime(visitBooking.visit_time_slot?.ends_at)}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    {visitBooking.visitors_count}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            {#if hasPermission('admin.visit-bookings.show')}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={route('admin.visit-bookings.show', { visitBooking: visitBooking.id })}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-search-list"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">View</span>
                                                                </a>
                                                            </div>
                                                            {/if}
                                                            {#if hasPermission('admin.visit-bookings.destroy')}
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link" on:click={() => deleteVisitBooking(visitBooking.id)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-trash"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Remove</span>
                                                                    </button>
                                                                </div>
                                                            {/if}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {/if}
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {#if pagination && pagination.total > 0}
                        <Pagination 
                            {pagination} 
                            {perPage}
                            onPageChange={goToPage} 
                            onPerPageChange={handlePerPageChange}
                        />
                    {/if}
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout> 