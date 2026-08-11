<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';

    // Props from the server
    export let programs;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Programs',
            url: route('admin.programs.index'),
            active: false
        },
        {
            title: 'Order',
            url: route('admin.programs.order-page'),
            active: true
        }
    ];
    
    const pageTitle = 'Order Programs';

    // Loading state
    let loading = false;
    let sortableList;

    // Handle order update
    async function updateOrder() {
        loading = true;
        
        const items = [];
        const children = Array.from(sortableList.children);
        
        children.forEach((el, index) => {
            if (el.dataset.id) {
                items.push({
                    id: el.dataset.id,
                    order: index + 1
                });
            }
        });
        
        try {
            const response = await fetch(route('admin.programs.order'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order: items })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: 'Program order updated successfully!',
                    variant: "success",
                    position: "bottom-right",
                });
            } else {
                throw new Error(data.message || 'Failed to update order');
            }
        } catch (error) {
            console.error('Error updating order:', error);
            // Show error toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                message: error.message || 'Failed to update program order',
                variant: "error",
                position: "bottom-right",
            });
        } finally {
            loading = false;
        }
    }

    // Initialize sortable after mount
    onMount(async () => {
        await tick();
        
        // Wait a bit more to ensure DOM is fully ready
        setTimeout(() => {
            // Check if Sortable is available
            if (typeof window.Sortable !== 'undefined') {
                const sortable = new window.Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        updateOrder();
                    }
                });
            } else if (typeof Sortable !== 'undefined') {
                const sortable = new Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        updateOrder();
                    }
                });
            } else {
                console.error('SortableJS not loaded. Please ensure SortableJS is included in your app.blade.php');
                // Show error message to user
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                    message: 'SortableJS not loaded. Please refresh the page.',
                    variant: "error",
                    position: "bottom-right",
                });
            }
        }, 100);
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Program Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Order Programs</h1>
                    <p class="text-sm text-secondary-foreground">
                        Drag and drop programs to reorder them
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.programs.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Programs
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Sortable List -->
                    <div class="w-full">
                        {#if programs && programs.length > 0}
                            <ul bind:this={sortableList} class="sortable-list">
                                {#each programs as program}
                                    <li class="list-group-item" data-id={program.id}>
                                        <div class="flex items-center">
                                            <i class="ki-filled ki-arrows-move handle"></i>
                                            <div class="flex-1">
                                                <span class="font-medium text-mono">{program.name}</span>
                                                <small class="text-secondary-foreground ml-2">(ID: {program.id})</small>
                                            </div>
                                            {#if loading}
                                                <div class="ml-3">
                                                    <i class="ki-outline ki-loading text-base animate-spin text-primary"></i>
                                                </div>
                                            {/if}
                                        </div>
                                    </li>
                                {/each}
                            </ul>
                        {:else}
                            <div class="text-center py-8">
                                <i class="ki-filled ki-files text-4xl text-secondary-foreground mb-4"></i>
                                <p class="text-secondary-foreground">No programs found to order.</p>
                                <a href="{route('admin.programs.create')}" class="kt-btn kt-btn-primary mt-4">
                                    <i class="ki-filled ki-plus text-base"></i>
                                    Create Program
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 