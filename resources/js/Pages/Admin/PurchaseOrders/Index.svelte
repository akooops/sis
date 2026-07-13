<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import PurchaseOrderLinesDrawer from './Lines/PurchaseOrderLinesDrawer.svelte';
    import ShippingsDrawer from './Shippings/ShippingsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Purchase Orders',
            url: route('web.admin.purchase-orders.index'),
            active: false,
        },
        {
            title: 'Index',
            url: route('web.admin.purchase-orders.index'),
            active: true,
        },
    ];

    const pageTitle = 'Purchase Orders';

    let purchaseOrders = [];
    let search = '';
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let filters = {
        sort_direction: 'desc',
        status: '',
        supplier_id: '',
        production_site_id: '',
    };

    let selectedPurchaseOrder = null;
    let selectedPurchaseOrderForLines = null;
    let selectedPurchaseOrderForShippings = null;
    let showCreateForm = false;
    let showEditForm = false;
    let editingPurchaseOrder = null;

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

    async function fetchPurchaseOrders() {
        loading = true;

        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.status) {
                queryParams.status = filters.status;
            }
            if (filters.supplier_id) {
                queryParams.supplier_id = filters.supplier_id;
            }
            if (filters.production_site_id) {
                queryParams.production_site_id = filters.production_site_id;
            }

            const response = await fetch(route('api.v1.admin.purchase-orders.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();
            purchaseOrders = data.purchase_orders || [];
            pagination = data.pagination || {};

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching purchase orders:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchPurchaseOrders();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchPurchaseOrders();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchPurchaseOrders();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchPurchaseOrders();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#purchase_orders_filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openViewDrawer(purchaseOrder) {
        selectedPurchaseOrder = purchaseOrder;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#purchase_order_view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openLinesDrawer(purchaseOrder) {
        selectedPurchaseOrderForLines = purchaseOrder;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#purchase_order_lines_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleLinesUpdated() {
        fetchPurchaseOrders();
    }

    function openShippingsDrawer(purchaseOrder) {
        selectedPurchaseOrderForShippings = purchaseOrder;
        document.querySelector('[data-kt-drawer-toggle="#purchase_order_shippings_drawer"]')?.click();
    }

    function handleShippingsUpdated() {
        fetchPurchaseOrders();
    }

    function handleRowClick(purchaseOrder) {
        openViewDrawer(purchaseOrder);
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
        showEditForm = false;
        editingPurchaseOrder = null;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchPurchaseOrders();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(purchaseOrder = null) {
        if (purchaseOrder?.id) {
            editingPurchaseOrder = purchaseOrder;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingPurchaseOrder = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingPurchaseOrder = null;
        fetchPurchaseOrders();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingPurchaseOrder = null;
    }

    async function handleConfirmPurchaseOrder(purchaseOrder) {
        const confirmed = confirm(`Are you sure you want to confirm "${purchaseOrder.name}"?\n\nThis will lock the purchase order from further edits.`);

        if (!confirmed) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.purchase-orders.confirm', { purchaseOrder: purchaseOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Purchase order confirmed successfully', 'success');
                fetchPurchaseOrders();
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot confirm purchase order:\n';
                Object.entries(data.errors).forEach(([field, message]) => {
                    const text = Array.isArray(message) ? message[0] : message;
                    errorMessage += `• ${text}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while confirming the purchase order.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    async function handleCancelPurchaseOrder(purchaseOrder) {
        const confirmed = confirm(`Are you sure you want to cancel "${purchaseOrder.name}"?\n\nAll pending lines will also be cancelled. This action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.purchase-orders.cancel', { purchaseOrder: purchaseOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Purchase order cancelled successfully', 'success');
                fetchPurchaseOrders();
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot cancel purchase order:\n';
                Object.entries(data.errors).forEach(([field, message]) => {
                    const text = Array.isArray(message) ? message[0] : message;
                    errorMessage += `• ${text}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while cancelling the purchase order.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    onMount(() => {
        fetchPurchaseOrders();
    });
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
                        {#if !showCreateForm && !showEditForm}
                            <div class="flex items-center gap-2">
                                <SearchBar
                                    bind:value={search}
                                    placeholder="Search purchase orders..."
                                    debounceMs={500}
                                    on:search={handleSearchFromComponent}
                                />

                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter purchase orders"
                                    aria-label="Filter purchase orders"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton
                                    tableData={purchaseOrders}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'status', label: 'Status' },
                                        { key: 'supplier.name', label: 'Supplier' },
                                        { key: 'production_site.name', label: 'Production Site' },
                                        { key: 'description', label: 'Description' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' },
                                    ]}
                                    filename="purchase-orders"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('purchase-orders.store')}
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" on:click={handleCreateFormToggle}>
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Purchase Order
                                </button>
                            {/if}
                        {:else if showCreateForm}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={handleCreateFormToggle}>
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {:else if showEditForm}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={handleEditFormToggle}>
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
                        <EditForm
                            purchaseOrder={editingPurchaseOrder}
                            on:updated={handleFormUpdated}
                            on:documentDeleted={fetchPurchaseOrders}
                            on:canceled={handleEditFormCanceled}
                        />
                    </div>
                {:else}
                    <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;">
                                            <span class="kt-table-col whitespace-nowrap capitalize">ID</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Purchase Order</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Site</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Supplier</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Status</span>
                                        </th>
                                        <th class="w-[80px]">
                                            <span class="kt-table-col whitespace-nowrap capitalize">Actions</span>
                                        </th>
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
                                    {:else if purchaseOrders.length === 0}
                                        <tr>
                                            <td colspan="6" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="ki-filled ki-document text-4xl text-muted-foreground mb-4"></i>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground mb-4">
                                                        {search || filters.status === 'cancelled' || filters.supplier_id || filters.production_site_id
                                                            ? 'No results match your search or filter criteria.'
                                                            : 'No purchase orders have been created yet.'}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each purchaseOrders as purchaseOrder}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(purchaseOrder)}>
                                                    <span class="text-xs font-medium text-primary">#{purchaseOrder.id}</span>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">{purchaseOrder.name}</span>
                                                        {#if purchaseOrder.code}
                                                            <span class="text-xs text-muted-foreground">{purchaseOrder.code}</span>
                                                        {/if}
                                                    </div>
                                                </td>
                                                <td>
                                                    {#if purchaseOrder.supplier}
                                                        <div class="flex items-center gap-3 max-content">
                                                            <img
                                                                src={purchaseOrder.supplier.logo_url}
                                                                alt={purchaseOrder.supplier.name}
                                                                class="w-[30px] h-[30px] rounded-lg object-cover"
                                                            />
                                                            <div class="flex flex-col gap-1">
                                                                <span class="text-sm font-medium text-secondary-foreground">
                                                                    {purchaseOrder.supplier.name}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {#if purchaseOrder.production_site}
                                                        <div class="flex items-center gap-3 max-content">
                                                            <img
                                                                src={purchaseOrder.production_site.logo_url}
                                                                alt={purchaseOrder.production_site.name}
                                                                class="w-[30px] h-[30px] rounded-lg object-cover"
                                                            />
                                                            <div class="flex flex-col gap-1">
                                                                <span class="text-sm font-medium text-secondary-foreground">
                                                                    {purchaseOrder.production_site.name}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(purchaseOrder.status)} text-xs font-medium capitalize">
                                                        {formatStatus(purchaseOrder.status)}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(purchaseOrder)}>
                                                                        <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('purchase-order-lines.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openLinesDrawer(purchaseOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-list"></i></span>
                                                                            <span class="kt-menu-title">Lines</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('shippings.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openShippingsDrawer(purchaseOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-truck"></i></span>
                                                                            <span class="kt-menu-title">Shippings</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('purchase-orders.update') && purchaseOrder.can_be_updated}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(purchaseOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('purchase-orders.confirm') && purchaseOrder.can_be_confirmed}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleConfirmPurchaseOrder(purchaseOrder)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-circle-check"></i></span>
                                                                            <span class="kt-menu-title">Confirm</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('purchase-orders.cancel') && purchaseOrder.can_be_cancelled}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancelPurchaseOrder(purchaseOrder)}>
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

    <button style="display:none" data-kt-drawer-toggle="#purchase_orders_filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#purchase_order_view_drawer" aria-label="Toggle view drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#purchase_order_lines_drawer" aria-label="Toggle lines drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#purchase_order_shippings_drawer" aria-label="Toggle shippings drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedPurchaseOrder} />
    <PurchaseOrderLinesDrawer purchaseOrder={selectedPurchaseOrderForLines} on:linesUpdated={handleLinesUpdated} />
    <ShippingsDrawer purchaseOrder={selectedPurchaseOrderForShippings} on:shippingsUpdated={handleShippingsUpdated} />
</MainLayout>
