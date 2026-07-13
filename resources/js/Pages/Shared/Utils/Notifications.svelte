<script>
    import { onMount, onDestroy, tick } from 'svelte';

    let notifications = [];
    let pagination = {};
    let unreadCount = 0;
    let loading = false;
    let loadingMore = false;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let pollInterval;
    let drawerOpen = false;
    let alertedNotificationIds = new Set();
    let bellAudioContext = null;

    const POLL_INTERVAL_MS = 5000;
    let markingAllAsRead = false;
    let scrollContainer;

    // Pretty type label (e.g. warehouse.requisition.needs_approval -> Warehouse · Requisition · Needs approval)
    function formatType(type) {
        if (!type) return '';
        return type
            .split('.')
            .map(part => part.replace(/_/g, ' '))
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' · ');
    }

    // Priority icon mapping
    function getPriorityIcon(priority) {
        switch (priority) {
            case 'urgent':
                return 'fa-solid fa-circle-exclamation';
            case 'high':
                return 'fa-solid fa-triangle-exclamation';
            case 'medium':
                return 'fa-solid fa-bell';
            case 'low':
                return 'fa-solid fa-bell';
            default:
                return 'fa-solid fa-bell';
        }
    }

    function getPriorityColor(priority) {
        switch (priority) {
            case 'urgent':
                return 'text-destructive';
            case 'high':
                return 'text-warning';
            case 'medium':
                return 'text-primary';
            default:
                return 'text-muted-foreground';
        }
    }

    function isDrawerOpen() {
        const el = document.getElementById('notifications_drawer');

        return drawerOpen || (el !== null && !el.classList.contains('hidden'));
    }

    // Fetch first page of notifications (replaces existing list)
    async function fetchNotifications(reset = true, { silent = false } = {}) {
        if (reset) {
            if (!silent) {
                currentPage = 1;
                loading = true;
            }
        } else {
            loadingMore = true;
        }

        const page = reset ? 1 : currentPage;
        const perPageParam = silent && reset
            ? Math.max(perPage, notifications.length)
            : perPage;

        try {
            const response = await fetch(
                route('api.v1.admin.notifications.index', {
                    page,
                    per_page: perPageParam,
                    search: search,
                    sort_direction: 'desc',
                }),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            const data = await response.json();
            const scrollTop = silent ? scrollContainer?.scrollTop ?? 0 : 0;

            if (reset) {
                notifications = data.notifications || [];
                if (!silent) {
                    currentPage = 1;
                }
            } else {
                notifications = [...notifications, ...(data.notifications || [])];
            }
            pagination = data.pagination || {};

            if (silent && scrollContainer) {
                await tick();
                scrollContainer.scrollTop = scrollTop;
            }

            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            loading = false;
            loadingMore = false;
        }
    }

    // Load more notifications (infinite scroll)
    async function loadMore() {
        if (loadingMore || loading) return;
        if (!pagination || currentPage >= (pagination.last_page || 1)) return;

        currentPage += 1;
        await fetchNotifications(false);
    }

    // Fetch unread count (using index with read_status=unread, page=1, per_page=1)
    async function fetchUnreadCount() {
        try {
            const response = await fetch(
                route('api.v1.admin.notifications.index', {
                    read_status: 'unread',
                    per_page: 1,
                    page: 1,
                }),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            const data = await response.json();
            unreadCount = data.pagination?.total || 0;
        } catch (error) {
            console.error('Error fetching unread count:', error);
        }
    }

    async function fetchUnreadNotifications(limit = 25) {
        try {
            const response = await fetch(
                route('api.v1.admin.notifications.index', {
                    read_status: 'unread',
                    per_page: limit,
                    page: 1,
                    sort_direction: 'desc',
                }),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            const data = await response.json();
            unreadCount = data.pagination?.total || 0;

            return data.notifications || [];
        } catch (error) {
            console.error('Error fetching unread notifications:', error);

            return [];
        }
    }

    function truncateTitle(title, maxLength = 96) {
        const text = (title || '').trim();

        if (!text) {
            return 'New notification';
        }

        if (text.length <= maxLength) {
            return text;
        }

        return `${text.slice(0, maxLength - 1)}…`;
    }

    function showNewNotificationToast(notification) {
        if (typeof window.toast !== 'function') {
            return;
        }

        window.toast(truncateTitle(notification?.title), 'info');
    }

    function playBellSound() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;

            if (!AudioCtx) {
                return;
            }

            if (!bellAudioContext) {
                bellAudioContext = new AudioCtx();
            }

            if (bellAudioContext.state === 'suspended') {
                bellAudioContext.resume();
            }

            const ctx = bellAudioContext;
            const now = ctx.currentTime;

            const playTone = (frequency, start, duration, volume = 0.12) => {
                const oscillator = ctx.createOscillator();
                const gain = ctx.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(volume, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                oscillator.connect(gain);
                gain.connect(ctx.destination);
                oscillator.start(start);
                oscillator.stop(start + duration);
            };

            playTone(880, now, 0.18);
            playTone(1174.66, now + 0.13, 0.22, 0.1);
        } catch (error) {
            console.warn('Could not play notification bell:', error);
        }
    }

    function alertForNewNotifications(newNotifications) {
        if (!newNotifications.length) {
            return;
        }

        playBellSound();

        newNotifications.forEach((notification, index) => {
            setTimeout(() => {
                showNewNotificationToast(notification);
            }, index * 350);
        });
    }

    async function seedAlertedNotifications() {
        const unread = await fetchUnreadNotifications();

        unread.forEach((notification) => {
            alertedNotificationIds.add(notification.id);
        });
    }

    // Handle search with debouncing
    function handleSearchInput(event) {
        search = event.target.value;

        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(() => {
            fetchNotifications(true);
        }, 500);
    }

    // Mark notification as read
    async function markAsRead(notificationId) {
        try {
            const response = await fetch(
                route('api.v1.admin.notifications.mark-read', { notification: notificationId }),
                {
                    method: 'PATCH',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                    },
                },
            );

            if (response.ok) {
                notifications = notifications.map(n =>
                    n.id === notificationId
                        ? { ...n, read_at: new Date().toISOString() }
                        : n,
                );
                await fetchUnreadCount();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Mark all notifications as read
    async function markAllAsRead() {
        if (markingAllAsRead) return;

        markingAllAsRead = true;
        try {
            const response = await fetch(
                route('api.v1.admin.notifications.mark-all-read'),
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                    },
                },
            );

            if (response.ok) {
                const now = new Date().toISOString();
                notifications.forEach((notification, index) => {
                    setTimeout(() => {
                        notifications = notifications.map(n =>
                            n.id === notification.id && !n.read_at
                                ? { ...n, read_at: now }
                                : n,
                        );
                    }, index * 30);
                });
                await fetchUnreadCount();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        } finally {
            markingAllAsRead = false;
        }
    }

    // Delete a notification (for the current user only)
    async function deleteNotification(notificationId, event) {
        if (event) {
            event.stopPropagation();
        }

        try {
            const response = await fetch(
                route('api.v1.admin.notifications.destroy', { notification: notificationId }),
                {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                    },
                },
            );

            if (response.ok) {
                notifications = notifications.filter(n => n.id !== notificationId);
                await fetchUnreadCount();
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }

    // Handle notification click
    function handleNotificationClick(notification) {
        if (!notification.read_at) {
            markAsRead(notification.id);
        }

        if (notification.url) {
            window.location.href = notification.url;
        }
    }

    async function pollNotifications() {
        const unread = await fetchUnreadNotifications();
        const newNotifications = unread.filter(
            (notification) => !alertedNotificationIds.has(notification.id),
        );

        newNotifications.forEach((notification) => {
            alertedNotificationIds.add(notification.id);
        });

        alertForNewNotifications(newNotifications);

        if (isDrawerOpen()) {
            drawerOpen = true;
            await fetchNotifications(true, { silent: true });
        }
    }

    // Toggle drawer
    function toggleDrawer() {
        if (bellAudioContext?.state === 'suspended') {
            bellAudioContext.resume();
        }

        requestAnimationFrame(() => {
            drawerOpen = isDrawerOpen();
            if (drawerOpen) {
                fetchNotifications(true);
            }
        });
    }

    function closeDrawer() {
        drawerOpen = false;
    }

    // Format time ago
    function formatTimeAgo(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }

    // Auto-load more when scrolling near the bottom
    function handleScroll(event) {
        const el = event.target;
        if (!el) return;
        const threshold = 80;
        const reachedBottom =
            el.scrollHeight - el.scrollTop - el.clientHeight < threshold;
        if (reachedBottom) {
            loadMore();
        }
    }

    onMount(async () => {
        await seedAlertedNotifications();
        pollInterval = setInterval(pollNotifications, POLL_INTERVAL_MS);
    });

    onDestroy(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        if (bellAudioContext) {
            bellAudioContext.close();
            bellAudioContext = null;
        }
    });
</script>

<!-- Notifications Bell -->
<button
    class="kt-btn kt-btn-icon kt-btn-ghost rounded-full size-9 border-2 border-transparent hover:border-primary hover:bg-primary/10 relative"
    data-kt-drawer-toggle="#notifications_drawer"
    on:click={toggleDrawer}
    aria-label="Open notifications"
    data-kt-tooltip=""
    data-kt-tooltip-placement="right"
>
    <i class="fa-solid fa-bell text-sm"></i>

    <!-- Unread count badge -->
    {#if unreadCount > 0}
        <span
            class="absolute -top-1 -right-1 bg-destructive text-white text-[10px] leading-none font-semibold rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center border-2 border-background"
            style="width: 18px; height: 18px;"
        >
            {unreadCount > 99 ? '99+' : unreadCount}
        </span>
    {/if}

    <span class="kt-tooltip" data-kt-tooltip-content="true">
        Notifications
    </span>
</button>

<!-- Notifications Drawer -->
<div
    class="hidden kt-drawer kt-drawer-end card flex flex-col min-h-0 max-h-[calc(100dvh-2.5rem)] max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden"
    data-kt-drawer="true"
    data-kt-drawer-container="body"
    id="notifications_drawer"
>
    <div class="kt-drawer-header shrink-0">
        <div class="flex items-center gap-2">
            <span class="kt-drawer-title">Notifications</span>
            {#if unreadCount > 0}
                <span class="kt-badge kt-badge-destructive text-xs">
                    {unreadCount}
                </span>
            {/if}
        </div>
        <button
            type="button"
            class="kt-drawer-close kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0"
            data-kt-drawer-dismiss="true"
            on:click={closeDrawer}
            aria-label="Close notifications drawer"
        >
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div
        class="kt-drawer-content kt-scrollable grow min-h-0 min-w-0 flex flex-col p-0"
        id="notifications_drawer_body"
        bind:this={scrollContainer}
        on:scroll={handleScroll}
    >
        <div class="px-5 pt-2 pb-3 border-b border-b-border shrink-0">
            <div class="kt-input max-w-full">
                <i class="fa-solid fa-magnifying-glass text-muted-foreground"></i>
                <input
                    type="text"
                    placeholder="Search notifications..."
                    bind:value={search}
                    on:input={handleSearchInput}
                />
            </div>
        </div>

        {#if loading}
            <div class="flex flex-col min-w-0">
                {#each Array(perPage) as _, i}
                    <div
                        class="flex min-w-0 gap-2.5 px-5 py-3 border-b border-b-border last:border-b-0"
                    >
                        <div class="kt-skeleton size-9 shrink-0 rounded-full"></div>
                        <div class="flex flex-1 min-w-0 flex-col gap-2">
                            <div class="kt-skeleton h-4 w-3/4 rounded"></div>
                            <div class="kt-skeleton h-3 w-full rounded"></div>
                            <div class="kt-skeleton h-3 w-1/2 rounded"></div>
                        </div>
                    </div>
                {/each}
            </div>
        {:else if notifications.length === 0}
            <div class="flex flex-col items-center justify-center py-10 px-5 text-center">
                <i class="fa-solid fa-bell-slash text-4xl text-muted-foreground my-4"
                ></i>
                <p class="text-sm text-muted-foreground">
                    {search
                        ? 'No notifications match your search.'
                        : "You're all caught up!"}
                </p>
            </div>
        {:else}
            <div class="flex flex-col min-w-0">
                {#each notifications as notification (notification.id)}
                    <div
                        class="group flex min-w-0 gap-2.5 px-5 py-3 border-b border-b-border last:border-b-0 hover:bg-muted transition-colors cursor-pointer {notification.read_at
                            ? 'opacity-75'
                            : ''}"
                        on:click={() => handleNotificationClick(notification)}
                        on:keydown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                handleNotificationClick(notification);
                            }
                        }}
                        role="button"
                        tabindex="0"
                    >
                        <!-- Icon -->
                        <div class="shrink-0">
                            <div
                                class="flex items-center justify-center size-9 rounded-full bg-primary/10 relative"
                            >
                                <i
                                    class="{getPriorityIcon(
                                        notification.priority,
                                    )} {getPriorityColor(
                                        notification.priority,
                                    )} text-sm"
                                ></i>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex flex-col flex-1 min-w-0 gap-1 overflow-hidden">
                            <div class="flex items-start justify-between gap-2 min-w-0">
                                <span
                                    class="text-sm font-semibold text-mono line-clamp-1 min-w-0 {notification.read_at
                                        ? 'text-muted-foreground'
                                        : ''}"
                                >
                                    {notification.title}
                                </span>
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                    on:click={(e) =>
                                        deleteNotification(notification.id, e)}
                                    aria-label="Delete notification"
                                    title="Delete"
                                >
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            {#if notification.message}
                                <p
                                    class="text-xs text-secondary-foreground line-clamp-2 break-words"
                                >
                                    {notification.message}
                                </p>
                            {/if}

                            <div class="flex items-center gap-1.5 mt-0.5 min-w-0">
                                <span
                                    class="text-xs font-medium text-muted-foreground shrink-0"
                                >
                                    {formatTimeAgo(notification.created_at)}
                                </span>
                                {#if notification.type}
                                    <span
                                        class="rounded-full size-1 bg-muted-foreground/40 shrink-0"
                                    ></span>
                                    <span
                                        class="text-xs font-medium text-muted-foreground line-clamp-1 min-w-0"
                                    >
                                        {formatType(notification.type)}
                                    </span>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/each}

                <!-- Load more / loading state -->
                {#if pagination && currentPage < (pagination.last_page || 1)}
                    <div class="flex items-center justify-center p-3 px-5">
                        <button
                            type="button"
                            class="kt-btn kt-btn-sm kt-btn-ghost"
                            on:click={loadMore}
                            disabled={loadingMore}
                        >
                            {#if loadingMore}
                                <i class="fa-solid fa-spinner fa-spin mr-2"
                                ></i>
                                Loading...
                            {:else}
                                <i class="fa-solid fa-chevron-down mr-2"></i>
                                Load more
                            {/if}
                        </button>
                    </div>
                {:else if loadingMore}
                    <div class="flex items-center justify-center p-3 px-5">
                        <i class="fa-solid fa-spinner fa-spin text-primary"></i>
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    {#if notifications.length > 0 || unreadCount > 0}
        <div class="kt-drawer-footer shrink-0" id="notifications_drawer_footer">
            <div class="grid w-full grid-cols-2 gap-2.5">
                <button
                    class="kt-btn kt-btn-sm kt-btn-outline justify-center {markingAllAsRead
                        ? 'opacity-75 cursor-not-allowed'
                        : ''}"
                    on:click={markAllAsRead}
                    disabled={markingAllAsRead || unreadCount === 0}
                >
                    {#if markingAllAsRead}
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Marking...
                    {:else}
                        <i class="fa-solid fa-check-double mr-2"></i>
                        Mark all read
                    {/if}
                </button>
                <a
                    class="kt-btn kt-btn-sm kt-btn-primary justify-center"
                    href={route('web.admin.notifications.index')}
                >
                    <i class="fa-solid fa-list mr-2"></i>
                    View all
                </a>
            </div>
        </div>
    {/if}
</div>
<!-- End of Notifications -->
