<script>
    import { createEventDispatcher, tick } from 'svelte';
    import SearchBar from '../../../Shared/Utils/Forms/SearchBar.svelte';
    import Pagination from '../../../Shared/Utils/Pagination.svelte';
    import CreateForm from '../../PurchaseOrderLines/CreateForm.svelte';
    import EditForm from '../../PurchaseOrderLines/EditForm.svelte';
    import ViewDrawer from '../../PurchaseOrderLines/ViewDrawer.svelte';
    import { fly } from 'svelte/transition';

    const dispatch = createEventDispatcher();

    export let purchaseOrder = null;

    let purchaseOrderLines = [];
    let search = '';
    let loading = true;
    let errors = {};

    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let showCreateForm = false;
    let showEditForm = false;
    let editingPurchaseOrderLine = null;
    let selectedPurchaseOrderLine = null;

    $: if (purchaseOrder) {
        fetchPurchaseOrderLines();
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'confirmed') return 'kt-badge-success';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    async function fetchPurchaseOrderLines() {
        if (!purchaseOrder) return;

        loading = true;
        errors = {};

        try {
            const response = await fetch(route('api.v1.admin.purchase-order-lines.index-purchase-order', {
                purchaseOrder: purchaseOrder.id,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: 'asc',
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                purchaseOrderLines = data.purchase_order_lines || [];
                pagination = data.pagination || {};

                await tick();

                if (window.KTMenu) {
                    window.KTMenu.init();
                }
            }
        } catch (error) {
            console.error('Error loading purchase order lines:', error);
            errors = { general: 'Failed to load purchase order lines. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchPurchaseOrderLines();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchPurchaseOrderLines();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchPurchaseOrderLines();
    }

    function showLinesTable() {
        showCreateForm = false;
        showEditForm = false;
        editingPurchaseOrderLine = null;
    }

    function showAddLineForm() {
        showCreateForm = true;
        showEditForm = false;
        editingPurchaseOrderLine = null;
    }

    function showEditLineForm(purchaseOrderLine) {
        editingPurchaseOrderLine = purchaseOrderLine;
        showEditForm = true;
        showCreateForm = false;
    }

    function openViewDrawer(purchaseOrderLine) {
        selectedPurchaseOrderLine = purchaseOrderLine;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#purchase_order_line_view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleRowClick(purchaseOrderLine) {
        openViewDrawer(purchaseOrderLine);
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchPurchaseOrderLines();
        dispatch('linesUpdated');
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingPurchaseOrderLine = null;
        fetchPurchaseOrderLines();
        dispatch('linesUpdated');
    }

    function handleFormCanceled() {
        showCreateForm = false;
        showEditForm = false;
        editingPurchaseOrderLine = null;
    }

    async function handleCancelPurchaseOrderLine(purchaseOrderLine) {
        const label = purchaseOrderLine.code || `#${purchaseOrderLine.id}`;
        const confirmed = confirm(`Are you sure you want to cancel line "${label}"?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.purchase-order-lines.cancel', { purchaseOrderLine: purchaseOrderLine.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Purchase order line cancelled successfully', 'success');
                fetchPurchaseOrderLines();
                dispatch('linesUpdated');
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot cancel purchase order line:\n';
                Object.entries(data.errors).forEach(([, message]) => {
                    const text = Array.isArray(message) ? message[0] : message;
                    errorMessage += `• ${text}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while cancelling the line.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    $: canManageLines = purchaseOrder?.status === 'pending';
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x" data-kt-drawer="true" data-kt-drawer-container="body" id="purchase_order_lines_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2 min-w-0">
            Purchase Order Lines:
            {#if purchaseOrder}
                <span class="text-muted-foreground truncate">{purchaseOrder.name}</span>
            {/if}
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card w-full">
        <div class="kt-card-header">
            <div class="kt-card-toolbar flex items-center justify-between w-full">
                {#if showCreateForm || showEditForm}
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={showLinesTable}>
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Back
                    </button>
                {:else}
                    <div class="flex items-center justify-between w-full gap-2">
                        <SearchBar
                            bind:value={search}
                            placeholder="Search lines..."
                            debounceMs={500}
                            showScanner={false}
                            on:search={handleSearchFromComponent}
                        />

                        {#if canManageLines && hasPermission('purchase-order-lines.store')}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-primary shrink-0" on:click={showAddLineForm}>
                                <i class="fa-solid fa-plus mr-1"></i>
                                Add Line
                            </button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>

        {#if showCreateForm}
            <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                <CreateForm purchaseOrderId={purchaseOrder?.id} on:created={handleFormCreated} on:canceled={handleFormCanceled} />
            </div>
        {:else if showEditForm}
            <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                <EditForm
                    purchaseOrderLine={editingPurchaseOrderLine}
                    on:updated={handleFormUpdated}
                    on:documentDeleted={fetchPurchaseOrderLines}
                    on:canceled={handleFormCanceled}
                />
            </div>
        {:else}
            <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                {#if errors.general}
                    <div class="p-4">
                        <p class="text-sm text-destructive">{errors.general}</p>
                    </div>
                {/if}

                <div class="kt-scrollable-x-auto kt-card-table">
                    <table class="kt-table kt-table-auto kt-table-border text-sm">
                        <thead>
                            <tr>
                                <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Line</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Quantity</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Status</span></th>
                                <th class="w-[80px]"><span class="kt-table-col whitespace-nowrap capitalize">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if loading}
                                {#each Array(perPage) as _, i}
                                    <tr>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                    </tr>
                                {/each}
                            {:else if purchaseOrderLines.length === 0}
                                <tr>
                                    <td colspan="6" class="p-10">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <i class="fa-solid fa-list text-4xl text-muted-foreground mb-4"></i>
                                            <h3 class="text-lg font-semibold text-mono mb-2">No lines found</h3>
                                            <p class="text-sm text-secondary-foreground">
                                                {search ? 'No lines match your search criteria.' : 'This purchase order has no lines yet.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            {:else}
                                {#each purchaseOrderLines as purchaseOrderLine}
                                    <tr class="hover:bg-muted cursor-pointer">
                                        <td on:click={() => handleRowClick(purchaseOrderLine)}>
                                            <span class="text-xs font-medium text-primary">#{purchaseOrderLine.id}</span>
                                        </td>
                                        <td on:click={() => handleRowClick(purchaseOrderLine)}>
                                            <div class="flex flex-col gap-1">
                                                <span class="text-sm font-medium text-secondary-foreground">{purchaseOrderLine.line}</span>
                                                {#if purchaseOrderLine.code}
                                                    <span class="text-xs text-muted-foreground">{purchaseOrderLine.code}</span>
                                                {/if}
                                            </div>
                                        </td>
                                        <td on:click={() => handleRowClick(purchaseOrderLine)}>
                                            {#if purchaseOrderLine.product}
                                                <div class="flex items-center gap-3 max-content">
                                                    <img src={purchaseOrderLine.product.image_url} alt={purchaseOrderLine.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">{purchaseOrderLine.product.name}</span>
                                                    </div>
                                                </div>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                            {/if}
                                        </td>
                                        <td on:click={() => handleRowClick(purchaseOrderLine)}>
                                            {#if formatQuantityWithUnit(purchaseOrderLine.product, purchaseOrderLine.quantity)}
                                                <span class="text-sm font-medium">{formatQuantityWithUnit(purchaseOrderLine.product, purchaseOrderLine.quantity)}</span>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                            {/if}
                                        </td>
                                        <td on:click={() => handleRowClick(purchaseOrderLine)}>
                                            <span class="kt-badge kt-badge-outline {getStatusBadgeClass(purchaseOrderLine.status)} text-xs font-medium capitalize">
                                                {formatStatus(purchaseOrderLine.status)}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="kt-menu flex-inline" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                        <div class="kt-menu-item">
                                                            <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(purchaseOrderLine)}>
                                                                <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                <span class="kt-menu-title">View</span>
                                                            </button>
                                                        </div>
                                                        {#if purchaseOrderLine.can_be_updated && hasPermission('purchase-order-lines.update')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => showEditLineForm(purchaseOrderLine)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                    <span class="kt-menu-title">Edit</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                        {#if hasPermission('purchase-order-lines.cancel') && purchaseOrderLine.can_be_cancelled}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancelPurchaseOrderLine(purchaseOrderLine)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-ban"></i></span>
                                                                    <span class="kt-menu-title">Cancel</span>
                                                                </button>
                                                            </div>
                                                        {/if}
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

                {#if pagination && pagination.total > 0}
                    <Pagination {pagination} {perPage} onPageChange={goToPage} onPerPageChange={handlePerPageChange} />
                {/if}
            </div>
        {/if}
    </div>

    <button style="display:none" data-kt-drawer-toggle="#purchase_order_line_view_drawer" aria-label="Toggle view drawer"></button>
    <ViewDrawer {selectedPurchaseOrderLine} />
</div>
