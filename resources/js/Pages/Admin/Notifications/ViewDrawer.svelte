<script>
    // Notification data
    export let selectedNotification = null;

    // Priority colors
    const priorityClasses = {
        urgent: 'kt-badge-destructive',
        high: 'kt-badge-warning',
        medium: 'kt-badge-primary',
        low: 'kt-badge-secondary',
        none: 'kt-badge-secondary',
    };

    // Pretty type label (e.g. warehouse.requisition.needs_approval -> Warehouse · Requisition · Needs approval)
    function formatType(type) {
        if (!type) return '';
        return type
            .split('.')
            .map(part => part.replace(/_/g, ' '))
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' · ');
    }

    // Close drawer
    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<!-- Notification Details Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Notification Details
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer} aria-label="Close notification details drawer">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedNotification}
            <div class="space-y-4">
                <!-- Title + status -->
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-base font-semibold text-mono leading-snug word-break">
                        {selectedNotification.title}
                    </h3>
                    <span class="kt-badge {selectedNotification.read_at ? 'kt-badge-secondary' : 'kt-badge-success'} text-xs shrink-0">
                        {selectedNotification.read_at ? 'Read' : 'New'}
                    </span>
                </div>

                <!-- Message -->
                {#if selectedNotification.message}
                    <p class="text-sm text-secondary-foreground word-break whitespace-pre-line">
                        {selectedNotification.message}
                    </p>
                {/if}

                <div class="border-t border-border w-full"></div>

                <!-- Meta -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                        <span class="text-sm font-medium word-break">#{selectedNotification.id}</span>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Type:</span>
                        <span class="text-sm font-medium text-mono word-break text-right">
                            {formatType(selectedNotification.type)}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Priority:</span>
                        <span class="kt-badge {priorityClasses[selectedNotification.priority] || 'kt-badge-secondary'} text-xs capitalize">
                            {selectedNotification.priority || 'none'}
                        </span>
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Received:</span>
                        <span class="text-sm font-medium">{formatTimeStamp(selectedNotification.created_at)}</span>
                    </div>

                    {#if selectedNotification.read_at}
                        <div class="flex items-start justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Read at:</span>
                            <span class="text-sm font-medium">{formatTimeStamp(selectedNotification.read_at)}</span>
                        </div>
                    {/if}
                </div>
            </div>
        {:else}
            <!-- Empty state -->
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-bell text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No Notification Selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select a notification to view its details.
                </p>
            </div>
        {/if}
    </div>
</div>
