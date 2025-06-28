<script>
    import { createEventDispatcher, onMount } from 'svelte';
    
    const dispatch = createEventDispatcher();
    
    // Props
    export let page = null;
    export let isOpen = false;
    
    // Get status badge class
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'published':
                return 'kt-badge-success';
            case 'draft':
                return 'kt-badge-info';
            case 'hidden':
                return 'kt-badge-primary';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get status text
    function getStatusText(status) {
        switch (status) {
            case 'published':
                return 'Published';
            case 'draft':
                return 'Draft';
            case 'hidden':
                return 'Hidden';
            default:
                return status;
        }
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Close drawer
    function closeDrawer() {
        dispatch('close');
    }

    onMount(() => {
        // Listen for Metronic drawer events
        const drawer = document.getElementById('page_details_drawer');
        if (drawer) {
            drawer.addEventListener('shown.bs.drawer', () => {
                // Drawer is shown
            });
            
            drawer.addEventListener('hidden.bs.drawer', () => {
                // Drawer is hidden, notify parent
                dispatch('close');
            });
        }
    });
</script>

<!-- Page Details Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="page_details_drawer">
    <!-- Header -->
    <div class="flex items-center justify-end gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <!-- Content -->
    <div class="grow kt-scrollable-y-auto" data-kt-scrollable="true" data-kt-scrollable-dependencies="#header" data-kt-scrollable-max-height="auto" data-kt-scrollable-offset="150px">
        {#if page}
            <div class="flex flex-col gap-5 p-5">
                <!-- Page Information -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Page Information</h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex flex-col gap-1">
                                <img 
                                    src={page.thumbnailUrl} 
                                    alt={page.name}
                                    class="w-20 h-20 rounded-lg object-cover border border-border"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Page ID</span>
                                <span class="text-sm font-medium text-mono">#{page.id}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Name</span>
                                <span class="text-sm font-medium text-mono">{page.name}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Slug</span>
                                <span class="text-sm font-medium text-mono">{page.slug}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Status</span>
                                <span class="text-sm font-medium text-mono">
                                    {getStatusText(page.status)}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">System Page</span>
                                <span class="text-sm font-medium text-mono">
                                    {page.is_system_page ? 'Yes' : 'No'}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Created At</span>
                                <span class="text-sm font-medium text-mono">{formatDate(page.created_at)}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Updated At</span>
                                <span class="text-sm font-medium text-mono">{formatDate(page.updated_at)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Content Preview -->
                {#if page.content}
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Content Preview</h3>
                        </div>
                        <div class="kt-card-content">
                            <div class="prose prose-sm max-w-none">
                                <div class="text-sm text-secondary-foreground overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 6; -webkit-box-orient: vertical; text-overflow: ellipsis;">
                                    {page.content}
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        {:else}
            <!-- Loading state -->
            <div class="flex items-center justify-center p-10">
                <div class="flex flex-col items-center gap-3">
                    <div class="kt-skeleton w-12 h-12 rounded-full"></div>
                    <div class="kt-skeleton w-32 h-4 rounded"></div>
                    <div class="kt-skeleton w-24 h-3 rounded"></div>
                </div>
            </div>
        {/if}
    </div>
</div>
<!-- End of Page Details Drawer --> 