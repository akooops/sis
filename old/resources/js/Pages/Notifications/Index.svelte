<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import { onMount, tick } from 'svelte';
    import { page } from '@inertiajs/svelte'

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Notifications',
            url: route('admin.notifications.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('admin.notifications.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Notifications';

    // Reactive variables
    let notifications = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;

    // Fetch notifications data
    async function fetchNotifications() {
        loading = true;
        try {
            const response = await fetch(route('admin.notifications.index', {
                page: currentPage,
                perPage: perPage,
                search: search
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            notifications = data.notifications;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
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
            fetchNotifications();
        }, 500);
    }

    // Handle search input change
    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchNotifications();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchNotifications();
    }

    // Mark notification as read
    async function markAsRead(notificationId) {
        try {
            await fetch(route('admin.notifications.mark-read', { notification: notificationId }), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            // Update local state
            const notification = notifications.find(n => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
            }
            
            // Show success toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Notification marked as read",
                variant: "success",
                position: "bottom-right",
            });
        } catch (error) {
            console.error('Error marking notification as read:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Error marking notification as read",
                variant: "destructive",
                position: "bottom-right",
            });
        }
    }

    // Mark all notifications as read
    async function markAllAsRead() {
        try {
            await fetch(route('admin.notifications.mark-all-read'), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            // Update local state
            notifications.forEach(notification => {
                notification.read_at = new Date().toISOString();
            });
            
            // Show success toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "All notifications marked as read",
                variant: "success",
                position: "bottom-right",
            });
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Error marking all notifications as read",
                variant: "destructive",
                position: "bottom-right",
            });
        }
    }

    // Get notification type badge class
    function getTypeBadgeClass(type) {
        switch (type) {
            case 'contact_submission':
                return 'kt-badge-primary';
            case 'inquiry':
                return 'kt-badge-info';
            case 'visit_booking':
                return 'kt-badge-success';
            case 'job_application':
                return 'kt-badge-warning';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get notification type label
    function getTypeLabel(type) {
        switch (type) {
            case 'contact_submission':
                return 'Contact';
            case 'inquiry':
                return 'Inquiry';
            case 'visit_booking':
                return 'Visit';
            case 'job_application':
                return 'Job';
            default:
                return type;
        }
    }

    // Get status badge class
    function getStatusBadgeClass(isRead) {
        return isRead ? 'kt-badge-secondary' : 'kt-badge-success';
    }

    // Get status text
    function getStatusText(isRead) {
        return isRead ? 'Read' : 'New';
    }

    // Format time ago
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }

    // Handle notification click
    function handleNotificationClick(notification) {
        // Mark as read if unread
        if (!notification.read_at) {
            markAsRead(notification.id);
        }
        
        // Open URL if available
        if (notification.url) {
            window.location.href = notification.url;
        }
    }

    onMount(() => {
        fetchNotifications();
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
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Notifications Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Notifications Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage and view all system notifications
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="kt-btn kt-btn-outline" on:click={markAllAsRead}>
                        <i class="ki-filled ki-check text-base"></i>
                        Mark All as Read
                    </button>
                </div>
            </div>

            <!-- Notifications Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="kt-input max-w-64 w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input 
                                type="text" 
                                class="kt-input" 
                                placeholder="Search notifications..." 
                                bind:value={search}
                                on:input={handleSearchInput}
                            />
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
                                            <span class="kt-table-col-label">Notification</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                        </span>
                                    </th>
                                    <th class="w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                        </span>
                                    </th>
                                    <th class="w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Date</span>
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
                                                <div class="flex items-center gap-3">
                                                    <div class="kt-skeleton w-10 h-10 rounded-lg"></div>
                                                    <div class="flex flex-col gap-1">
                                                        <div class="kt-skeleton w-24 h-4 rounded"></div>
                                                        <div class="kt-skeleton w-16 h-3 rounded"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-12 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-8 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if notifications.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="7" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-notification-status text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No notifications found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search ? 'No notifications match your search criteria.' : 'No notifications have been created yet.'}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each notifications as notification}
                                        <tr class="hover:bg-muted/50">
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={notification.id}/>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">#{notification.id}</span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                                            <i class="{notification.icon} text-lg text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-mono hover:text-primary cursor-pointer" on:click={() => handleNotificationClick(notification)}>
                                                            {notification.title}
                                                        </span>
                                                        <p class="text-xs text-secondary-foreground line-clamp-1">
                                                            {notification.message}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="kt-badge {getTypeBadgeClass(notification.type)}">
                                                    {getTypeLabel(notification.type)}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="kt-badge {getStatusBadgeClass(notification.read_at)}">
                                                    {getStatusText(notification.read_at)}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-xs text-muted-foreground">
                                                    {formatTimeAgo(notification.created_at)}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            {#if !notification.read_at}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" on:click={() => markAsRead(notification.id)}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-check"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Mark as Read</span>
                                                                </button>
                                                            </div>
                                                            {/if}
                                                            {#if notification.url}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={notification.url}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-arrow-right"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">View Details</span>
                                                                </a>
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