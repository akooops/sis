<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';

    // Props from the server
    export let program;
    export let programStreams;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Programs',
            url: route('admin.programs.index'),
            active: false
        },
        {
            title: program?.name || 'Program',
            url: route('admin.program-streams.index', { program: program?.id }),
            active: false
        },
        {
            title: 'Streams',
            url: route('admin.program-streams.index', { program: program?.id }),
            active: false
        },
        {
            title: 'Order',
            url: route('admin.program-streams.order-page', { program: program?.id }),
            active: true
        }
    ];

    const pageTitle = 'Order Streams';

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
            const response = await fetch(route('admin.program-streams.order', { program: program.id }), {
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
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: 'Stream order updated successfully!',
                    variant: "success",
                    position: "bottom-right",
                });
            } else {
                throw new Error(data.message || 'Failed to update order');
            }
        } catch (error) {
            console.error('Error updating order:', error);
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                message: error.message || 'Failed to update stream order',
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

        setTimeout(() => {
            if (typeof window.Sortable !== 'undefined') {
                new window.Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function (evt) {
                        updateOrder();
                    }
                });
            } else if (typeof Sortable !== 'undefined') {
                new Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function (evt) {
                        updateOrder();
                    }
                });
            } else {
                console.error('SortableJS not loaded. Please ensure SortableJS is included in your app.blade.php');
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
            <!-- Stream Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Order Streams</h1>
                    <p class="text-sm text-secondary-foreground">
                        Drag and drop streams of "{program?.name}" to reorder them
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.program-streams.index', { program: program?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Streams
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Sortable List -->
                    <div class="w-full">
                        {#if programStreams && programStreams.length > 0}
                            <ul bind:this={sortableList} class="sortable-list">
                                {#each programStreams as programStream}
                                    <li class="list-group-item" data-id={programStream.id}>
                                        <div class="flex items-center">
                                            <i class="ki-filled ki-arrows-move handle"></i>
                                            <div class="flex-1">
                                                <span class="font-medium text-mono">{programStream.name}</span>
                                                <small class="text-secondary-foreground ml-2">(ID: {programStream.id})</small>
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
                                <p class="text-secondary-foreground">No streams found to order.</p>
                                <a href="{route('admin.program-streams.create', { program: program?.id })}" class="kt-btn kt-btn-primary mt-4">
                                    <i class="ki-filled ki-plus text-base"></i>
                                    Create Stream
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout>
