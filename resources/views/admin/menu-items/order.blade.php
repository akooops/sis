<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';

    // Props from the server
    export let menu;
    export let menuItems;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Menus Management',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: menu?.name || 'Menu',
            url: route('admin.menu-items.index', { menu: menu?.id }),
            active: false
        },
        {
            title: 'Order Menu Items',
            url: route('admin.menu-items.order-page', { menu: menu?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Order Menu Items';

    // Loading state
    let loading = false;
    let sortableContainer;

    // Recursive component for nested menu items
    function createNestedItem(item) {
        return {
            ...item,
            children: item.children || []
        };
    }

    // Initialize sortable functionality
    function initSortable() {
        // Get all nested sortable elements
        const nestedSortables = document.querySelectorAll('.sortable-list');
        
        // Loop through each nested sortable element
        for (let i = 0; i < nestedSortables.length; i++) {
            new Sortable(nestedSortables[i], {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                emptyInsertThreshold: 5,
                onEnd: function(evt) {
                    // Re-initialize sortables for any new nested lists
                    setTimeout(initSortable, 100);
                    updateOrder();
                }
            });
        }
    }

    // Handle order update with nested support
    async function updateOrder() {
        loading = true;
        
        const items = [];
        
        function processList(list, parentId = null) {
            const children = Array.from(list.children);
            children.forEach((el, index) => {
                if (el.dataset.id) {
                    const itemData = {
                        id: el.dataset.id,
                        order: index + 1,
                        menu_item_id: parentId
                    };
                    
                    items.push(itemData);

                    // Process child lists
                    const childList = el.querySelector('.sortable-list');
                    if (childList) {
                        processList(childList, itemData.id);
                    }
                }
            });
        }

        const rootList = document.getElementById('sortable');
        processList(rootList);
        
        try {
            const response = await fetch(route('admin.menu-items.order', { menu: menu.id }), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order: items })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: 'Menu item order updated successfully!',
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
                message: error.message || 'Failed to update menu item order',
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
                initSortable();
            } else if (typeof Sortable !== 'undefined') {
                initSortable();
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
            <!-- Menu Items Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Order Menu Items</h1>
                    <p class="text-sm text-secondary-foreground">
                        Drag and drop items to reorder. Drop items onto other items to create submenus.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menu-items.index', { menu: menu?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Menu Items
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Info Alert -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="ki-filled ki-information text-blue-600 mr-2"></i>
                            <p class="text-sm text-blue-800">
                                Drag and drop items to reorder. Drop items onto other items to create submenus.
                            </p>
                        </div>
                    </div>

                    <!-- Sortable List -->
                    <div class="w-full">
                        {#if menuItems && menuItems.length > 0}
                            <ul id="sortable" class="sortable-list">
                                {#each menuItems as item}
                                    {@const nestedItem = createNestedItem(item)}
                                    <li class="list-group-item" data-id={nestedItem.id}>
                                        <div class="flex items-center">
                                            <i class="ki-filled ki-arrows-move handle"></i>
                                            <div class="flex-1">
                                                <span class="font-medium text-mono">{nestedItem.name}</span>
                                                <small class="text-secondary-foreground ml-2">(ID: {nestedItem.id})</small>
                                            </div>
                                            {#if loading}
                                                <div class="ml-3">
                                                    <i class="ki-outline ki-loading text-base animate-spin text-primary"></i>
                                                </div>
                                            {/if}
                                        </div>
                                        
                                        <!-- Always create a nested list, even if empty -->
                                        <ul class="sortable-list">
                                            {#if nestedItem.children && nestedItem.children.length > 0}
                                                {#each nestedItem.children as child}
                                                    {@const nestedChild = createNestedItem(child)}
                                                    <li class="list-group-item" data-id={nestedChild.id}>
                                                        <div class="flex items-center">
                                                            <i class="ki-filled ki-arrows-move handle"></i>
                                                            <div class="flex-1">
                                                                <span class="font-medium text-mono">{nestedChild.name}</span>
                                                                <small class="text-secondary-foreground ml-2">(ID: {nestedChild.id})</small>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Nested children (you can extend this for deeper nesting) -->
                                                        <ul class="sortable-list">
                                                            {#if nestedChild.children && nestedChild.children.length > 0}
                                                                {#each nestedChild.children as grandchild}
                                                                    {@const nestedGrandchild = createNestedItem(grandchild)}
                                                                    <li class="list-group-item" data-id={nestedGrandchild.id}>
                                                                        <div class="flex items-center">
                                                                            <i class="ki-filled ki-arrows-move handle"></i>
                                                                            <div class="flex-1">
                                                                                <span class="font-medium text-mono">{nestedGrandchild.name}</span>
                                                                                <small class="text-secondary-foreground ml-2">(ID: {nestedGrandchild.id})</small>
                                                                            </div>
                                                                        </div>
                                                                        <ul class="sortable-list"></ul>
                                                                    </li>
                                                                {/each}
                                                            {/if}
                                                        </ul>
                                                    </li>
                                                {/each}
                                            {/if}
                                        </ul>
                                    </li>
                                {/each}
                            </ul>
                        {:else}
                            <div class="text-center py-8">
                                <i class="ki-filled ki-menu text-4xl text-secondary-foreground mb-4"></i>
                                <p class="text-secondary-foreground">No menu items found to order.</p>
                                <a href="{route('admin.menu-items.create', { menu: menu?.id })}" class="kt-btn kt-btn-primary mt-4">
                                    <i class="ki-filled ki-plus text-base"></i>
                                    Create Menu Item
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout>

<style>
    .sortable-list {
        list-style: none;
        padding: 0;
        margin: 0;
        min-height: 20px; /* Important for empty lists */
    }
    
    .sortable-list ul {
        margin-left: 30px;
        list-style: none;
        padding: 0;
        min-height: 20px; /* Important for nesting */
    }
    
    .sortable-list .list-group-item {
        cursor: move;
        margin: 5px 0;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 10px;
        border-radius: 5px;
        position: relative;
    }
    
    .handle {
        margin-right: 10px;
        cursor: move;
        color: #6c757d;
    }
    
    .sortable-ghost {
        opacity: 0.4;
    }
    
    .sortable-chosen {
        background-color: #e3f2fd;
    }
    
    .sortable-drag {
        background-color: #fff3cd;
    }
</style>
