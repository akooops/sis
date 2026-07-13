<script>
    import { createEventDispatcher, tick } from 'svelte';
    import SearchBar from '../../../Shared/Utils/Forms/SearchBar.svelte';
    import Pagination from '../../../Shared/Utils/Pagination.svelte';
    import CreateForm from '../../Shippings/CreateForm.svelte';
    import EditForm from '../../Shippings/EditForm.svelte';
    import ReceiveForm from '../../Shippings/ReceiveForm.svelte';
    import ViewDrawer from '../../Shippings/ViewDrawer.svelte';
    import { advanceShipping, getAdvanceLabel } from '../../Shippings/shippingActions.js';
    import { fly } from 'svelte/transition';

    const dispatch = createEventDispatcher();

    export let purchaseOrder = null;

    let shippings = [];
    let search = '';
    let loading = true;
    let errors = {};

    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let showCreateForm = false;
    let showEditForm = false;
    let showReceiveForm = false;
    let editingShipping = null;
    let receivingShipping = null;
    let selectedShipping = null;

    $: if (purchaseOrder) {
        fetchShippings();
    }

    $: canManageShippings = purchaseOrder?.status === 'confirmed';

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    function getStatusBadgeClass(status) {
        if (status === 'in_transit') return 'kt-badge-warning';
        if (status === 'at_customs') return 'kt-badge-info';
        if (status === 'delivered') return 'kt-badge-primary';
        if (status === 'received') return 'kt-badge-success';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatTransportMode(mode) {
        if (!mode) return 'N/A';
        return mode.charAt(0).toUpperCase() + mode.slice(1);
    }

    async function fetchShippings() {
        if (!purchaseOrder) return;

        loading = true;
        errors = {};

        try {
            const response = await fetch(route('api.v1.admin.shippings.index-purchase-order', {
                purchaseOrder: purchaseOrder.id,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: 'desc',
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                shippings = data.shippings || [];
                pagination = data.pagination || {};
                await tick();
                if (window.KTMenu) window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error loading shippings:', error);
            errors = { general: 'Failed to load shippings. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchShippings();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchShippings();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchShippings();
    }

    function showShippingsTable() {
        showCreateForm = false;
        showEditForm = false;
        showReceiveForm = false;
        editingShipping = null;
        receivingShipping = null;
    }

    function showAddShippingForm() {
        showCreateForm = true;
        showEditForm = false;
        showReceiveForm = false;
        editingShipping = null;
        receivingShipping = null;
    }

    function showEditShippingForm(shipping) {
        editingShipping = shipping;
        showEditForm = true;
        showCreateForm = false;
        showReceiveForm = false;
    }

    function showReceiveShippingForm(shipping) {
        receivingShipping = shipping;
        showReceiveForm = true;
        showCreateForm = false;
        showEditForm = false;
    }

    function openViewDrawer(shipping) {
        selectedShipping = shipping;
        document.querySelector('[data-kt-drawer-toggle="#shipping_view_drawer"]')?.click();
    }

    function handleRowClick(shipping) {
        openViewDrawer(shipping);
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchShippings();
        dispatch('shippingsUpdated');
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingShipping = null;
        fetchShippings();
        dispatch('shippingsUpdated');
    }

    function handleReceived() {
        showReceiveForm = false;
        receivingShipping = null;
        fetchShippings();
        dispatch('shippingsUpdated');
    }

    function handleFormCanceled() {
        showShippingsTable();
    }

    async function handleAdvanceShipping(shipping) {
        const advanced = await advanceShipping(shipping);
        if (advanced) {
            fetchShippings();
            dispatch('shippingsUpdated');
        }
    }

    async function handleCancelShipping(shipping) {
        const label = shipping.code || `#${shipping.id}`;
        if (!confirm(`Are you sure you want to cancel shipping "${label}"?\n\nThis action cannot be undone.`)) return;

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.shippings.cancel', { shipping: shipping.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Shipping cancelled successfully', 'success');
                fetchShippings();
                dispatch('shippingsUpdated');
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot cancel shipping:\n';
                Object.entries(data.errors).forEach(([, message]) => {
                    errorMessage += `• ${Array.isArray(message) ? message[0] : message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while cancelling the shipping.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x" data-kt-drawer="true" data-kt-drawer-container="body" id="purchase_order_shippings_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2 min-w-0">
            Shippings:
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
                {#if showCreateForm || showEditForm || showReceiveForm}
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={showShippingsTable}>
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Back
                    </button>
                {:else}
                    <div class="flex items-center justify-between w-full gap-2">
                        <SearchBar bind:value={search} placeholder="Search shippings..." debounceMs={500} showScanner={false} on:search={handleSearchFromComponent} />
                        {#if canManageShippings && hasPermission('shippings.store')}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-primary shrink-0" on:click={showAddShippingForm}>
                                <i class="fa-solid fa-plus mr-1"></i>
                                Add Shipping
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
                <EditForm shipping={editingShipping} on:updated={handleFormUpdated} on:documentDeleted={fetchShippings} on:canceled={handleFormCanceled} />
            </div>
        {:else if showReceiveForm}
            <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                <ReceiveForm shipping={receivingShipping} on:received={handleReceived} on:canceled={handleFormCanceled} />
            </div>
        {:else}
            <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                {#if errors.general}
                    <div class="p-4"><p class="text-sm text-destructive">{errors.general}</p></div>
                {/if}

                <div class="kt-scrollable-x-auto kt-card-table">
                    <table class="kt-table kt-table-auto kt-table-border text-sm">
                        <thead>
                            <tr>
                                <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">PO Line</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Quantity</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Transport</span></th>
                                <th><span class="kt-table-col whitespace-nowrap capitalize">Status</span></th>
                                <th class="w-[80px]"><span class="kt-table-col whitespace-nowrap capitalize">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if loading}
                                {#each Array(perPage) as _}
                                    <tr>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                    </tr>
                                {/each}
                            {:else if shippings.length === 0}
                                <tr>
                                    <td colspan="7" class="p-10">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <i class="fa-solid fa-truck text-4xl text-muted-foreground mb-4"></i>
                                            <h3 class="text-lg font-semibold text-mono mb-2">No shippings found</h3>
                                            <p class="text-sm text-secondary-foreground">
                                                {search ? 'No shippings match your search criteria.' : 'This purchase order has no shippings yet.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            {:else}
                                {#each shippings as shipping}
                                    <tr class="hover:bg-muted cursor-pointer">
                                        <td on:click={() => handleRowClick(shipping)}>
                                            <span class="text-xs font-medium text-primary">#{shipping.id}</span>
                                        </td>
                                        <td on:click={() => handleRowClick(shipping)}>
                                            {#if shipping.purchase_order_line}
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium">{shipping.purchase_order_line.line}</span>
                                                    {#if shipping.purchase_order_line.code}
                                                        <span class="text-xs text-muted-foreground">{shipping.purchase_order_line.code}</span>
                                                    {/if}
                                                </div>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                            {/if}
                                        </td>
                                        <td on:click={() => handleRowClick(shipping)}>
                                            {#if shipping.product}
                                                <div class="flex items-center gap-3">
                                                    <img src={shipping.product.image_url} alt={shipping.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                                                    <span class="text-sm font-medium">{shipping.product.name}</span>
                                                </div>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                            {/if}
                                        </td>
                                        <td on:click={() => handleRowClick(shipping)}>
                                            {#if formatQuantityWithUnit(shipping.product, shipping.quantity)}
                                                <span class="text-sm font-medium">{formatQuantityWithUnit(shipping.product, shipping.quantity)}</span>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                            {/if}
                                        </td>
                                        <td on:click={() => handleRowClick(shipping)}>
                                            <span class="text-sm">{formatTransportMode(shipping.mode_of_transportation)}</span>
                                        </td>
                                        <td on:click={() => handleRowClick(shipping)}>
                                            <span class="kt-badge kt-badge-outline {getStatusBadgeClass(shipping.status)} text-xs font-medium capitalize">
                                                {formatStatus(shipping.status)}
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
                                                            <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(shipping)}>
                                                                <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                <span class="kt-menu-title">View</span>
                                                            </button>
                                                        </div>
                                                        {#if shipping.can_be_updated && hasPermission('shippings.update')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => showEditShippingForm(shipping)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                    <span class="kt-menu-title">Edit</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                        {#if shipping.can_be_advanced && hasPermission('shippings.advance')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleAdvanceShipping(shipping)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-arrow-right"></i></span>
                                                                    <span class="kt-menu-title">{getAdvanceLabel(shipping)}</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                        {#if shipping.can_be_received && hasPermission('shippings.receive')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => showReceiveShippingForm(shipping)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-box-open"></i></span>
                                                                    <span class="kt-menu-title">Receive</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                        {#if hasPermission('shippings.cancel') && shipping.can_be_cancelled}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancelShipping(shipping)}>
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

    <button style="display:none" data-kt-drawer-toggle="#shipping_view_drawer" aria-label="Toggle view drawer"></button>
    <ViewDrawer {selectedShipping} />
</div>
