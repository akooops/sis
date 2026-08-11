<script>
    import { onMount, tick } from 'svelte';

    let notifications = [];
    let pagination = {};
    let unreadCount = 0;
    let loading = false;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let drawerOpen = false;
    let markingAllAsRead = false;

    // Fetch notifications from API
    async function fetchNotifications() {
        try {
            loading = true;
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

    // Fetch unread count
    async function fetchUnreadCount() {
        try {
            const response = await fetch(route('admin.notifications.unread-count'));
            const data = await response.json();
            unreadCount = data.count;
        } catch (error) {
            console.error('Error fetching unread count:', error);
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
                notification.read_at = true;
                notification.read_at = new Date().toISOString();
            }
            
            // Refresh unread count
            await fetchUnreadCount();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Mark all notifications as read
    async function markAllAsRead() {
        if (markingAllAsRead) return; // Prevent multiple clicks
        
        try {
            markingAllAsRead = true;
            
            await fetch(route('admin.notifications.mark-all-read'), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            // Update local state with staggered animation
            notifications.forEach((notification, index) => {
                setTimeout(() => {
                    notification.read_at = new Date().toISOString();
                }, index * 50); // Stagger the animation by 50ms per notification
            });
            
            // Refresh unread count
            await fetchUnreadCount();
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        } finally {
            markingAllAsRead = false;
        }
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

    // Toggle drawer
    function toggleDrawer() {
        drawerOpen = !drawerOpen;
        if (drawerOpen) {
            fetchNotifications();
        }
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

    // Get notification type label
    function getNotificationTypeLabel(type) {
        const labels = {
            'contact_submission': 'Contact',
            'inquiry': 'Inquiry',
            'visit_booking': 'Visit',
            'job_application': 'Job'
        };
        return labels[type] || type;
    }

    // Get status badge class
    function getStatusBadgeClass(isRead) {
        return isRead ? 'kt-badge-secondary' : 'kt-badge-success';
    }

    onMount(() => {
        fetchUnreadCount();
        
        // Refresh unread count every 60 seconds
        setInterval(fetchUnreadCount, 60000);
    });
</script>

<!-- Notifications -->
<button 
    class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary relative" 
    data-kt-drawer-toggle="#notifications_drawer"
    on:click={toggleDrawer}
>
    <i class="ki-filled ki-notification-status text-lg"></i>
    
    <!-- Unread count badge -->
    {#if unreadCount > 0}
        <span class="absolute -top-1 -right-1 bg-danger kt-badge kt-badge-destructive text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {unreadCount > 99 ? '99+' : unreadCount}
        </span>
    {/if}
</button>

<!-- Notifications Drawer -->
<div 
    class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" 
    data-kt-drawer="true" 
    data-kt-drawer-container="body" 
    id="notifications_drawer"
>
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border" id="notifications_header">
        Notifications
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    
    <div class="kt-tabs kt-tabs-line justify-between px-5 mb-2" data-kt-tabs="true" id="notifications_tabs">
        <div class="flex items-center gap-5">
            <button class="kt-tab-toggle py-3 active" data-kt-tab-toggle="#notifications_tab_all">
                All
            </button>
        </div>
    </div>
    
    <div class="grow flex flex-col kt-scrollable-y-auto" id="notifications_tab_all" data-kt-scrollable="true" data-kt-scrollable-dependencies="#header" data-kt-scrollable-max-height="auto" data-kt-scrollable-offset="150px">
        <!-- Search Bar -->
        <div class="px-5 pb-3">
            <div class="kt-input max-w-full">
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
        
        <div>
            {#if loading}
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            {:else if notifications.length === 0}
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <i class="ki-filled ki-notification-status text-4xl text-muted-foreground mb-4"></i>
                    <p class="text-muted-foreground">
                        {search ? 'No notifications match your search criteria.' : 'No notifications yet'}
                    </p>
                </div>
            {:else}
                <div class="grow flex flex-col gap-5 pt-3 pb-4 divider-y divider-border">
                    {#each notifications as notification, index}
                        <div class="flex grow gap-2.5 px-5 transition-all duration-300 {notification.read_at ? 'opacity-75' : ''}">
                            <div class="kt-avatar size-8">
                                <div class="kt-avatar-image">
                                    <i class="{notification.icon} text-lg text-primary"></i>
                                </div>
                                {#if !notification.read_at}
                                    <div class="kt-avatar-indicator -end-2 -bottom-2">
                                        <div class="kt-avatar-status kt-avatar-status-online size-2.5"></div>
                                    </div>
                                {/if}
                            </div>
                            <div class="flex flex-col gap-1 flex-1">
                                <div class="text-sm font-medium mb-px">
                                    <button 
                                        class="hover:text-primary text-mono font-semibold text-left {notification.read_at ? 'text-muted-foreground' : ''}"
                                        style="cursor: pointer;"
                                        on:click={() => handleNotificationClick(notification)}
                                    >
                                        {notification.title}
                                    </button>
                                </div>
                                <p class="text-sm text-secondary-foreground line-clamp-2">
                                    {notification.message}
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center text-xs font-medium text-muted-foreground">
                                        {formatTimeAgo(notification.created_at)}
                                        <span class="rounded-full size-1 bg-mono/30 mx-1.5"></span>
                                        {getNotificationTypeLabel(notification.type)}
                                    </span>
                                    <span class="kt-badge {getStatusBadgeClass(notification.read_at)} text-xs transition-all duration-300 transform {notification.read_at ? 'scale-95' : 'scale-100'}">
                                        {notification.read_at ? 'Read' : 'New'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        {#if index < pagination.total - 1}
                            <div class="border-b border-b-border"></div>
                        {/if}
                    {/each}
                </div>
            {/if}
        </div>
        
        {#if pagination && pagination.total > 0}
            <div class="border-b border-b-border"></div>
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total} notifications
                    </div>
                    <div class="flex items-center gap-2">
                        {#if pagination.current_page > 1}
                            <button 
                                class="kt-btn kt-btn-sm kt-btn-outline"
                                on:click={() => goToPage(pagination.current_page - 1)}
                            >
                                Previous
                            </button>
                        {/if}
                        {#if pagination.current_page < pagination.last_page}
                            <button 
                                class="kt-btn kt-btn-sm kt-btn-outline"
                                on:click={() => goToPage(pagination.current_page + 1)}
                            >
                                Next
                            </button>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}
    </div>

    {#if notifications.length > 0}
    <div class="flex items-center justify-between" id="notifications_footer">
        <div class="border-b border-b-border"></div>
        <div class="grid grid-cols-2 p-5 gap-2.5" id="notifications_all_footer">
            <button 
                class="kt-btn kt-btn-outline justify-center transition-all duration-200 {markingAllAsRead ? 'opacity-75 cursor-not-allowed' : ''}" 
                on:click={markAllAsRead}
                disabled={markingAllAsRead}
            >
                {#if markingAllAsRead}
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2"></div>
                    Marking...
                {:else}
                    <i class="ki-filled ki-check text-base mr-2"></i>
                    Mark all as read
                {/if}
            </button>
            <a class="kt-btn kt-btn-primary justify-center" href={route('admin.notifications.index')}>
                View all
            </a>
        </div>
    </div>
    {/if}
</div>

<!-- End of Notifications -->