<script>
    import { createEventDispatcher } from "svelte";

    const dispatch = createEventDispatcher();

    // Filter state
    export let filters = {
        sort_direction: "asc",
    };

    let tempFilters = { ...filters };

    // Apply filters
    function applyFilters() {
        filters = { ...tempFilters };
        dispatch("filtersChanged", filters);
        closeDrawer();
    }

    // Reset filters
    function resetFilters() {
        tempFilters = {
            sort_direction: "asc",
        };
        filters = { ...tempFilters };
        dispatch("filtersChanged", filters);
        closeDrawer();
    }

    // Close drawer
    function closeDrawer() {
        const dismissButton = document.querySelector(
            '[data-kt-drawer-dismiss="#filters_drawer"]',
        );
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<!-- Filters Drawer -->
<div
    class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border"
    data-kt-drawer="true"
    data-kt-drawer-container="body"
    id="filters_drawer"
>
    <!-- Header -->
    <div
        class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border"
    >
        <div class="flex items-center gap-2">Filter Settings</div>
        <button
            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0"
            data-kt-drawer-dismiss="true"
            on:click={closeDrawer}
            aria-label="Close filters drawer"
        >
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <!-- Body -->
    <div
        class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto"
    >
        <!-- Sort Direction -->
        <div>
            <label
                class="text-sm font-semibold text-mono"
                for="sort_direction_filter">Sort Direction</label
            >
            <select
                id="sort_direction_filter"
                bind:value={tempFilters.sort_direction}
                class="kt-select"
            >
                <option value="asc">Ascending (A-Z)</option>
                <option value="desc">Descending (Z-A)</option>
            </select>
        </div>
    </div>

    <!-- Footer Actions -->
    <div
        class="flex items-center justify-between gap-2.5 px-5 py-2.5 border-t border-t-border"
    >
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
