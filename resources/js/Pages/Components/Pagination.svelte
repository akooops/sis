<script>
    export let pagination = {};
    export let onPageChange = () => {};
    export let perPage = 10;
    export let onPerPageChange = () => {};

    // Handle page navigation
    function goToPage(page) {
        if (page && page !== pagination.currentPage) {
            onPageChange(page);
        }
    }

    // Handle per page change
    function handlePerPageChange(event) {
        const newPerPage = parseInt(event.target.value);
        onPerPageChange(newPerPage);
    }
</script>

{#if pagination && pagination.total > 0}
    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
        <div class="flex items-center gap-4 order-2 md:order-1">
            <!-- Per Page Selector -->
            <div class="flex items-center gap-2">
                <span>Show:</span>
                <select 
                    class="kt-select w-16 h-8 text-xs" 
                    value={perPage} 
                    on:change={handlePerPageChange}
                >
                    <option value={1}>1</option>
                    <option value={5}>5</option>
                    <option value={10}>10</option>
                    <option value={25}>25</option>
                    <option value={50}>50</option>
                    <option value={100}>100</option>
                </select>
                <span>per page</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4 order-1 md:order-2 text-secondary-foreground text-sm font-medium">
                        
            <!-- Results Info -->
            <span>
                Showing {pagination.from} to {pagination.to} of {pagination.total} results
            </span>

            <div class="kt-pagination">
                <!-- Previous Page -->
                <button 
                    class="kt-pagination-item {!pagination.prevPage ? 'disabled' : ''}"
                    disabled={!pagination.prevPage}
                    on:click={() => goToPage(pagination.prevPage)}
                >
                    <i class="ki-filled ki-arrow-left"></i>
                </button>

                <!-- Page Numbers -->
                {#each pagination.pages || [] as pageNum}
                    <button 
                        class="kt-pagination-item {pageNum === pagination.currentPage ? 'active' : ''}"
                        on:click={() => goToPage(pageNum)}
                    >
                        {pageNum}
                    </button>
                {/each}

                <!-- Next Page -->
                <button 
                    class="kt-pagination-item {!pagination.nextPage ? 'disabled' : ''}"
                    disabled={!pagination.nextPage}
                    on:click={() => goToPage(pagination.nextPage)}
                >
                    <i class="ki-filled ki-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
{/if}

<style>
    .kt-pagination-item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 16px;
        height: 32px;
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        background-color: transparent;
        color: var(--muted-foreground);
        font-size: 0.45;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    
    .kt-pagination-item:hover:not(.disabled) {
        background-color: var(--accent);
        color: var(--accent-foreground);
        border-color: var(--border);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
    
    .kt-pagination-item.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: transparent;
        color: var(--muted-foreground);
    }
    
    .kt-pagination-item.active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        cursor: pointer;
        font-weight: 600;
    }
    
    .kt-pagination-item.active:hover {
        background-color: var(--primary);
        color: white;
        box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    }
</style> 