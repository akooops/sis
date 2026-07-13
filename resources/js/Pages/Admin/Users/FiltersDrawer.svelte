<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    
    const dispatch = createEventDispatcher();
    
    // Filter state
    export let filters = {
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
            Filter Users
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    
    <div class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto">
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
