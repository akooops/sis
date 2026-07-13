<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Filter state
    export let filters = {
        priority: '',
        read_status: 'all',
        sort_direction: 'desc'
    };

    let tempFilters = { ...filters };

    // Apply filters
    function applyFilters() {
        filters = { ...tempFilters };
        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    // Reset filters
    function resetFilters() {
        tempFilters = {
            priority: '',
            read_status: 'all',
            sort_direction: 'desc'
        };
        filters = { ...tempFilters };
        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    // Close drawer
    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#filters_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<!-- Filters Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="filters_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Filter Notifications
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto">
        <!-- Read Status -->
        <div>
            <label class="text-sm font-semibold text-mono">Status</label>
            <select bind:value={tempFilters.read_status} class="kt-select">
                <option value="all">All</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </select>
        </div>

        <!-- Priority -->
        <div>
            <label class="text-sm font-semibold text-mono">Priority</label>
            <select bind:value={tempFilters.priority} class="kt-select">
                <option value="">All</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
                <option value="none">None</option>
            </select>
        </div>

        <!-- Sort Direction -->
        <div>
            <label class="text-sm font-semibold text-mono">Sort Direction</label>
            <select bind:value={tempFilters.sort_direction} class="kt-select">
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
            </select>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-between gap-2.5 px-5 py-2.5 border-t border-t-border">
        <button
            type="button"
            class="kt-btn kt-btn-md kt-btn-ghost"
            on:click={resetFilters}
        >
            <i class="fa-solid fa-rotate-left mr-1"></i>
            Reset
        </button>

        <div class="flex gap-2">
            <button
                type="button"
                class="kt-btn kt-btn-md kt-btn-primary"
                on:click={applyFilters}
            >
                <i class="fa-solid fa-check mr-1"></i>
                Apply Filters
            </button>
        </div>
    </div>
</div>
