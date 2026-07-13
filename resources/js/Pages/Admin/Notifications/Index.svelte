<script>
    import MainLayout from "../../Shared/Layouts/MainLayout.svelte";
    import Pagination from "../../Shared/Utils/Pagination.svelte";
    import SearchBar from "../../Shared/Utils/Forms/SearchBar.svelte";
    import ExportButton from "../../Shared/Utils/ExportButton.svelte";

    import FiltersDrawer from "./FiltersDrawer.svelte";
    import ViewDrawer from "./ViewDrawer.svelte";
    import { onMount, tick } from "svelte";

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: "Notifications",
            url: route("web.admin.notifications.index"),
            active: false,
        },
        {
            title: "Index",
            url: route("web.admin.notifications.index"),
            active: true,
        },
    ];

    const pageTitle = "Notifications";

    // Props
    export let search = "";

    // Reactive variables
    let notifications = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let markingAll = false;

    // Filter state
    let filters = {
        priority: "",
        read_status: "all",
        sort_direction: "desc",
    };

    // View drawer state
    let selectedNotification = null;

    // Priority badge classes
    const priorityClasses = {
        urgent: "kt-badge-destructive",
        high: "kt-badge-warning",
        medium: "kt-badge-primary",
        low: "kt-badge-secondary",
        none: "kt-badge-secondary",
    };

    // Pretty type label
    function formatType(type) {
        if (!type) return "";
        return type
            .split(".")
            .map((part) => part.replace(/_/g, " "))
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(" · ");
    }

    // Fetch notifications data
    async function fetchNotifications() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
                read_status: filters.read_status,
            };

            if (filters.priority) {
                queryParams.priority = filters.priority;
            }

            const response = await fetch(
                route("api.v1.admin.notifications.index", queryParams),
                {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                },
            );

            const data = await response.json();
            notifications = data.notifications;
            pagination = data.pagination;

            // Wait for DOM to update, then initialize menus
            await tick();

            // Initialize KT components
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error("Error fetching notifications:", error);
        } finally {
            loading = false;
        }
    }

    // Handle search from SearchBar component
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchNotifications();
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

    // Handle filters change
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchNotifications();
    }

    // Open filters drawer
    function openFiltersDrawer() {
        const toggleButton = document.querySelector(
            '[data-kt-drawer-toggle="#filters_drawer"]',
        );
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Open view drawer
    function openViewDrawer(notification) {
        selectedNotification = notification;
        const toggleButton = document.querySelector(
            '[data-kt-drawer-toggle="#view_drawer"]',
        );
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Handle row click
    function handleRowClick(notification) {
        openViewDrawer(notification);
    }

    // Mark a single notification as read
    async function markAsRead(notification) {
        if (notification.read_at) return;

        try {
            const response = await fetch(
                route("api.v1.admin.notifications.mark-read", notification.id),
                {
                    method: "PATCH",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                },
            );

            if (response.ok) {
                notifications = notifications.map((n) =>
                    n.id === notification.id
                        ? { ...n, read_at: new Date().toISOString() }
                        : n,
                );
                toast("Notification marked as read", "success");
            } else {
                toast("Failed to mark notification as read", "error");
            }
        } catch (err) {
            console.error("Network error:", err);
            toast("Network error occurred", "error");
        }
    }

    // Mark all notifications as read
    async function markAllAsRead() {
        if (markingAll) return;

        markingAll = true;
        try {
            const response = await fetch(
                route("api.v1.admin.notifications.mark-all-read"),
                {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                },
            );

            if (response.ok) {
                const now = new Date().toISOString();
                notifications = notifications.map((n) =>
                    n.read_at ? n : { ...n, read_at: now },
                );
                toast("All notifications marked as read", "success");
            } else {
                toast("Failed to mark all as read", "error");
            }
        } catch (err) {
            console.error("Network error:", err);
            toast("Network error occurred", "error");
        } finally {
            markingAll = false;
        }
    }

    // Delete a notification (only for current user)
    async function handleDelete(notification) {
        const confirmed = confirm(
            "Are you sure you want to delete this notification?\n\nIt will only be removed from your inbox.",
        );

        if (!confirmed) return;

        try {
            const response = await fetch(
                route("api.v1.admin.notifications.destroy", notification.id),
                {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                },
            );

            if (response.ok) {
                toast("Notification deleted", "success");
                fetchNotifications();
            } else {
                const data = await response.json().catch(() => ({}));
                toast(data.message || "Failed to delete notification", "error");
            }
        } catch (err) {
            console.error("Network error:", err);
            toast("Network error occurred", "error");
        }
    }

    // Handle view drawer updates
    function handleNotificationUpdated() {
        fetchNotifications();
    }

    // Handle view drawer deletes
    function handleNotificationDeleted() {
        fetchNotifications();
    }

    onMount(() => {
        fetchNotifications();
    });
</script>

<svelte:head>
    <title>Mawdja - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="flex gap-5 lg:gap-7.5">
            <!-- Notifications Table -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <div
                        class="kt-card-toolbar flex items-center justify-between w-full"
                    >
                        <div class="flex items-center gap-2">
                            <SearchBar
                                bind:value={search}
                                placeholder="Search notifications..."
                                debounceMs={500}
                                showScanner={false}
                                on:search={handleSearchFromComponent}
                            />

                            <!-- Filter Button -->
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={openFiltersDrawer}
                                title="Filter notifications"
                                aria-label="Filter notifications"
                            >
                                <i class="fa-solid fa-filter"></i>
                            </button>

                            <ExportButton
                                tableData={notifications}
                                headers={[
                                    { key: "id", label: "ID" },
                                    { key: "title", label: "Title" },
                                    { key: "type", label: "Type" },
                                    { key: "priority", label: "Priority" },
                                    { key: "read_at", label: "Read At" },
                                    { key: "created_at", label: "Created" },
                                ]}
                                filename="notifications"
                                totalRecords={pagination?.total || 0}
                                currentPerPage={perPage}
                                {filters}
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-outline"
                                on:click={markAllAsRead}
                                disabled={markingAll}
                                title="Mark all as read"
                                aria-label="Mark all as read"
                            >
                                {#if markingAll}
                                    <i
                                        class="fa-solid fa-spinner fa-spin mr-1"
                                    ></i>
                                    Marking...
                                {:else}
                                    <i class="fa-solid fa-check-double mr-1"
                                    ></i>
                                    Mark all as read
                                {/if}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto kt-card-table">
                        <table
                            class="kt-table kt-table-auto kt-table-border text-sm overflow-hidden
"
                        >
                            <thead>
                                <tr>
                                    <th style="width: 115px;">
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            ID
                                        </span>
                                    </th>
                                    <th>
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Notification
                                        </span>
                                    </th>
                                    <th>
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Type
                                        </span>
                                    </th>
                                    <th>
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Priority
                                        </span>
                                    </th>
                                    <th>
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Status
                                        </span>
                                    </th>
                                    <th>
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Received
                                        </span>
                                    </th>
                                    <th class="w-[80px]">
                                        <span
                                            class="kt-table-col whitespace-nowrap capitalize"
                                        >
                                            Actions
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
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div>
                                            </td>
                                            <td class="p-4">
                                                <div
                                                    class="kt-skeleton w-8 h-8 rounded"
                                                ></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if notifications.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="7" class="p-10">
                                            <div
                                                class="flex flex-col items-center justify-center text-center"
                                            >
                                                <div class="mb-4">
                                                    <i
                                                        class="fa-solid fa-bell text-4xl text-muted-foreground"
                                                    ></i>
                                                </div>
                                                <h3
                                                    class="text-lg font-semibold text-mono mb-2"
                                                >
                                                    No results found
                                                </h3>
                                                <p
                                                    class="text-sm text-secondary-foreground mb-4"
                                                >
                                                    {search
                                                        ? "No notifications match your search criteria."
                                                        : "You don't have any notifications yet."}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each notifications as notification}
                                        <tr
                                            class="hover:bg-muted cursor-pointer {notification.read_at
                                                ? ''
                                                : 'bg-primary/5'}"
                                        >
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <span
                                                    class="text-xs font-medium text-primary"
                                                    >#{notification.id}</span
                                                >
                                            </td>
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-sm font-semibold text-foreground line-clamp-1"
                                                    >
                                                        {notification.title}
                                                    </span>
                                                    {#if notification.message}
                                                        <span
                                                            class="text-xs text-muted-foreground line-clamp-1"
                                                        >
                                                            {notification.message}
                                                        </span>
                                                    {/if}
                                                </div>
                                            </td>
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <span
                                                    class="text-xs font-medium text-mono"
                                                >
                                                    {formatType(
                                                        notification.type,
                                                    )}
                                                </span>
                                            </td>
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <span
                                                    class="kt-badge {priorityClasses[
                                                        notification.priority
                                                    ] ||
                                                        'kt-badge-secondary'} text-xs capitalize"
                                                >
                                                    {notification.priority ||
                                                        "none"}
                                                </span>
                                            </td>
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <span
                                                    class="kt-badge {notification.read_at
                                                        ? 'kt-badge-secondary'
                                                        : 'kt-badge-success'} text-xs"
                                                >
                                                    {notification.read_at
                                                        ? "Read"
                                                        : "New"}
                                                </span>
                                            </td>
                                            <td
                                                on:click={() =>
                                                    handleRowClick(
                                                        notification,
                                                    )}
                                            >
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {formatTimeStamp(
                                                        notification.created_at,
                                                    )}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div
                                                    class="kt-menu flex-inline"
                                                    data-kt-menu="true"
                                                >
                                                    <div
                                                        class="kt-menu-item"
                                                        data-kt-menu-item-offset="0, 10px"
                                                        data-kt-menu-item-placement="bottom-end"
                                                        data-kt-menu-item-placement-rtl="bottom-start"
                                                        data-kt-menu-item-toggle="dropdown"
                                                        data-kt-menu-item-trigger="click"
                                                    >
                                                        <button
                                                            class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                            aria-label="Open actions menu"
                                                        >
                                                            <i
                                                                class="ki-filled ki-dots-vertical text-lg"
                                                            ></i>
                                                        </button>
                                                        <div
                                                            class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]"
                                                        >
                                                            <div
                                                                class="kt-menu-item"
                                                            >
                                                                <button
                                                                    class="kt-menu-link"
                                                                    data-kt-menu-dismiss="true"
                                                                    on:click={() =>
                                                                        openViewDrawer(
                                                                            notification,
                                                                        )}
                                                                >
                                                                    <span
                                                                        class="kt-menu-icon"
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-eye"
                                                                        ></i>
                                                                    </span>
                                                                    <span
                                                                        class="kt-menu-title"
                                                                        >View</span
                                                                    >
                                                                </button>
                                                            </div>
                                                            {#if notification.url}
                                                                <div
                                                                    class="kt-menu-item"
                                                                >
                                                                    <a
                                                                        class="kt-menu-link"
                                                                        data-kt-menu-dismiss="true"
                                                                        href={notification.url}
                                                                    >
                                                                        <span
                                                                            class="kt-menu-icon"
                                                                        >
                                                                            <i
                                                                                class="fa-solid fa-arrow-up-right-from-square"
                                                                            ></i>
                                                                        </span>
                                                                        <span
                                                                            class="kt-menu-title"
                                                                            >Open</span
                                                                        >
                                                                    </a>
                                                                </div>
                                                            {/if}
                                                            {#if !notification.read_at}
                                                                <div
                                                                    class="kt-menu-item"
                                                                >
                                                                    <button
                                                                        class="kt-menu-link"
                                                                        data-kt-menu-dismiss="true"
                                                                        on:click={() =>
                                                                            markAsRead(
                                                                                notification,
                                                                            )}
                                                                    >
                                                                        <span
                                                                            class="kt-menu-icon"
                                                                        >
                                                                            <i
                                                                                class="fa-solid fa-check"
                                                                            ></i>
                                                                        </span>
                                                                        <span
                                                                            class="kt-menu-title"
                                                                            >Mark
                                                                            as
                                                                            read</span
                                                                        >
                                                                    </button>
                                                                </div>
                                                            {/if}
                                                            <div
                                                                class="kt-menu-item"
                                                            >
                                                                <button
                                                                    class="kt-menu-link"
                                                                    data-kt-menu-dismiss="true"
                                                                    on:click={() =>
                                                                        handleDelete(
                                                                            notification,
                                                                        )}
                                                                >
                                                                    <span
                                                                        class="kt-menu-icon"
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-trash"
                                                                        ></i>
                                                                    </span>
                                                                    <span
                                                                        class="kt-menu-title"
                                                                        >Delete</span
                                                                    >
                                                                </button>
                                                            </div>
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

    <!-- Hidden buttons to trigger drawers -->
    <button
        style="display:none"
        data-kt-drawer-toggle="#filters_drawer"
        aria-label="Toggle filters drawer"
    ></button>
    <button
        style="display:none"
        data-kt-drawer-toggle="#view_drawer"
        aria-label="Toggle view drawer"
    ></button>

    <!-- Filters Drawer -->
    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />

    <!-- View Drawer -->
    <ViewDrawer {selectedNotification}/>
</MainLayout>
