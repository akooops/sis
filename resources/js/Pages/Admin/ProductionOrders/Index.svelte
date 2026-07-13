<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import CompleteForm from './CompleteForm.svelte';
    import { advanceProductionOrder, getAdvanceLabel } from './productionOrderActions.js';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        { title: 'Production Orders', url: route('web.admin.production-orders.index'), active: false },
        { title: 'Index', url: route('web.admin.production-orders.index'), active: true },
    ];

    const pageTitle = 'Production Orders';

    let productionOrders = [];
    let search = '';
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let filters = {
        sort_direction: 'desc',
        status: '',
        product_id: '',
    };

    let showCreateForm = false;
    let showEditForm = false;
    let showCompleteForm = false;
    let editingProductionOrder = null;
    let completingProductionOrder = null;
    let selectedProductionOrder = null;

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'confirmed') return 'kt-badge-info';
        if (status === 'producing') return 'kt-badge-primary';
        if (status === 'completed') return 'kt-badge-success';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    async function fetchProductionOrders() {
        loading = true;

        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.status) queryParams.status = filters.status;
            if (filters.product_id) queryParams.product_id = filters.product_id;

            const response = await fetch(route('api.v1.admin.production-orders.index', queryParams), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();
            productionOrders = data.production_orders || [];
            pagination = data.pagination || {};

            await tick();
            if (window.KTMenu) window.KTMenu.init();
        } catch (error) {
            console.error('Error fetching production orders:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchProductionOrders();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchProductionOrders();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchProductionOrders();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchProductionOrders();
    }

    function openFiltersDrawer() {
        document.querySelector('[data-kt-drawer-toggle="#production_orders_filters_drawer"]')?.click();
    }

    function openViewDrawer(productionOrder) {
        selectedProductionOrder = productionOrder;
        document.querySelector('[data-kt-drawer-toggle="#production_order_view_drawer"]')?.click();
    }

    function handleRowClick(productionOrder) {
        openViewDrawer(productionOrder);
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
        showEditForm = false;
        showCompleteForm = false;
        editingProductionOrder = null;
        completingProductionOrder = null;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchProductionOrders();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(productionOrder = null) {
        if (productionOrder?.id) {
            editingProductionOrder = productionOrder;
            showEditForm = true;
            showCreateForm = false;
            showCompleteForm = false;
        } else {
            showEditForm = false;
            editingProductionOrder = null;
        }
    }

    function handleCompleteFormToggle(productionOrder = null) {
        if (productionOrder?.id) {
            completingProductionOrder = productionOrder;
            showCompleteForm = true;
            showCreateForm = false;
            showEditForm = false;
        } else {
            showCompleteForm = false;
            completingProductionOrder = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingProductionOrder = null;
        fetchProductionOrders();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingProductionOrder = null;
    }

    function handleCompleted() {
        showCompleteForm = false;
        completingProductionOrder = null;
        fetchProductionOrders();
    }

    function handleCompleteFormCanceled() {
        showCompleteForm = false;
        completingProductionOrder = null;
    }

    async function handleAdvanceProductionOrder(productionOrder) {
        const advanced = await advanceProductionOrder(productionOrder);
        if (advanced) {
            fetchProductionOrders();
        }
    }

    async function handleCancelProductionOrder(productionOrder) {
        const label = productionOrder.batch || productionOrder.code || `#${productionOrder.id}`;
        if (!confirm(`Are you sure you want to cancel production order "${label}"?\n\nThis action cannot be undone.`)) return;

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.production-orders.cancel', { productionOrder: productionOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Production order cancelled successfully', 'success');
                fetchProductionOrders();
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot cancel production order:\n';
                Object.entries(data.errors).forEach(([, message]) => {
                    errorMessage += `• ${Array.isArray(message) ? message[0] : message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while cancelling the production order.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    onMount(() => fetchProductionOrders());
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !showCreateForm && !showEditForm && !showCompleteForm}
                            <div class="flex items-center gap-2">
                                <SearchBar bind:value={search} placeholder="Search production orders..." debounceMs={500} on:search={handleSearchFromComponent} />
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" on:click={openFiltersDrawer} title="Filter production orders" aria-label="Filter production orders">
                                    <i class="fa-solid fa-filter"></i>
                                </button>
                                <ExportButton
                                    tableData={productionOrders}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'batch', label: 'Batch' },
                                        { key: 'product.name', label: 'Product' },
                                        { key: 'quantity_planned', label: 'Qty Planned' },
                                        { key: 'quantity_produced', label: 'Qty Produced' },
                                        { key: 'status', label: 'Status' },
                                        { key: 'planned_at', label: 'Planned At' },
                                        { key: 'created_at', label: 'Created' },
                                    ]}
                                    filename="production-orders"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>
                            {#if hasPermission('production-orders.store')}
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" on:click={handleCreateFormToggle}>
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Production Order
                                </button>
                            {/if}
                        {:else if showCreateForm}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={handleCreateFormToggle}>
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {:else if showEditForm}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={() => handleEditFormToggle()}>
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {:else if showCompleteForm}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={handleCompleteFormCanceled}>
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {/if}
                    </div>
                </div>

                {#if showCreateForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                        <CreateForm on:created={handleFormCreated} on:canceled={handleFormCanceled} />
                    </div>
                {:else if showEditForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                        <EditForm productionOrder={editingProductionOrder} on:updated={handleFormUpdated} on:documentDeleted={fetchProductionOrders} on:canceled={handleEditFormCanceled} />
                    </div>
                {:else if showCompleteForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                        <CompleteForm productionOrder={completingProductionOrder} on:completed={handleCompleted} on:canceled={handleCompleteFormCanceled} />
                    </div>
                {:else}
                    <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Batch</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Qty Planned</span></th>
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
                                                <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                            </tr>
                                        {/each}
                                    {:else if productionOrders.length === 0}
                                        <tr>
                                            <td colspan="6" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="fa-solid fa-industry text-4xl text-muted-foreground mb-4"></i>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground">No production orders match your criteria.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each productionOrders as productionOrder}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(productionOrder)}>
                                                    <span class="text-xs font-medium text-primary">#{productionOrder.id}</span>
                                                </td>
                                                <td on:click={() => handleRowClick(productionOrder)}>
                                                    {#if productionOrder.batch}
                                                        <span class="text-sm font-medium text-mono">{productionOrder.batch}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(productionOrder)}>
                                                    {#if productionOrder.product}
                                                        <div class="flex items-center gap-3">
                                                            <img src={productionOrder.product.image_url} alt={productionOrder.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                                                            <span class="text-sm font-medium">{productionOrder.product.name}</span>
                                                        </div>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(productionOrder)}>
                                                    {#if formatQuantityWithUnit(productionOrder.product, productionOrder.quantity_planned)}
                                                        <span class="text-sm font-medium">{formatQuantityWithUnit(productionOrder.product, productionOrder.quantity_planned)}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(productionOrder)}>
                                                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(productionOrder.status)} text-xs font-medium capitalize">
                                                        {formatStatus(productionOrder.status)}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(productionOrder)}>
                                                                        <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('production-orders.update') && productionOrder.can_be_updated}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(productionOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-orders.advance') && productionOrder.can_be_advanced}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleAdvanceProductionOrder(productionOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-arrow-right"></i></span>
                                                                            <span class="kt-menu-title">{getAdvanceLabel(productionOrder)}</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-orders.complete') && productionOrder.can_be_completed}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleCompleteFormToggle(productionOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-check"></i></span>
                                                                            <span class="kt-menu-title">Complete</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-orders.cancel') && productionOrder.can_be_cancelled}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancelProductionOrder(productionOrder)}>
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
        </div>
    </div>

    <button style="display:none" data-kt-drawer-toggle="#production_orders_filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#production_order_view_drawer" aria-label="Toggle view drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedProductionOrder} />
</MainLayout>
