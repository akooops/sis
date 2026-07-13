<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import ShippingsDrawer from '../PurchaseOrders/Shippings/ShippingsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Purchase Order Lines',
            url: route('web.admin.purchase-order-lines.index'),
            active: false,
        },
        {
            title: 'Index',
            url: route('web.admin.purchase-order-lines.index'),
            active: true,
        },
    ];

    const pageTitle = 'Purchase Order Lines';

    let purchaseOrderLines = [];
    let search = '';
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let filters = {
        sort_direction: 'asc',
        status: '',
        product_id: '',
        purchase_order_id: '',
        production_site_id: '',
    };

    let showCreateForm = false;
    let showEditForm = false;
    let editingPurchaseOrderLine = null;
    let selectedPurchaseOrderLine = null;
    let selectedPurchaseOrderForShippings = null;

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

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    async function fetchPurchaseOrderLines() {
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
            if (filters.product_id) {
                queryParams.product_id = filters.product_id;
            }
            if (filters.purchase_order_id) {
                queryParams.purchase_order_id = filters.purchase_order_id;
            }
            if (filters.production_site_id) {
                queryParams.production_site_id = filters.production_site_id;
            }

            const response = await fetch(route('api.v1.admin.purchase-order-lines.index-all', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();
            purchaseOrderLines = data.purchase_order_lines || [];
            pagination = data.pagination || {};

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching purchase order lines:', error);
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

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchPurchaseOrderLines();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#purchase_order_lines_filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
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

    function openShippingsDrawer(purchaseOrder) {
        selectedPurchaseOrderForShippings = purchaseOrder;
        document.querySelector('[data-kt-drawer-toggle="#purchase_order_shippings_drawer"]')?.click();
    }

    function handleShippingsDrawerUpdated() {
        fetchPurchaseOrderLines();
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
        showEditForm = false;
        editingPurchaseOrderLine = null;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchPurchaseOrderLines();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(purchaseOrderLine = null) {
        if (purchaseOrderLine?.id) {
            editingPurchaseOrderLine = purchaseOrderLine;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingPurchaseOrderLine = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingPurchaseOrderLine = null;
        fetchPurchaseOrderLines();
    }

    function handleEditFormCanceled() {
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

    onMount(() => {
        fetchPurchaseOrderLines();
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
                                    placeholder="Search purchase order lines..."
                                    debounceMs={500}
                                    on:search={handleSearchFromComponent}
                                />

                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter purchase order lines"
                                    aria-label="Filter purchase order lines"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton
                                    tableData={purchaseOrderLines}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'line', label: 'Line' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'purchase_order.name', label: 'Purchase Order' },
                                        { key: 'product.name', label: 'Product' },
                                        { key: 'quantity', label: 'Quantity' },
                                        { key: 'status', label: 'Status' },
                                        { key: 'notes', label: 'Notes' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' },
                                    ]}
                                    filename="purchase-order-lines"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('purchase-order-lines.store')}
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" on:click={handleCreateFormToggle}>
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Line
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
                            purchaseOrderLine={editingPurchaseOrderLine}
                            on:updated={handleFormUpdated}
                            on:documentDeleted={fetchPurchaseOrderLines}
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
                                            <span class="kt-table-col whitespace-nowrap capitalize">Line</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Purchase Order</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Product</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Quantity</span>
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
                                                <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                                <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                            </tr>
                                        {/each}
                                    {:else if purchaseOrderLines.length === 0}
                                        <tr>
                                            <td colspan="7" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="fa-solid fa-list text-4xl text-muted-foreground mb-4"></i>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground mb-4">
                                                        {search || filters.status || filters.product_id || filters.purchase_order_id || filters.production_site_id
                                                            ? 'No results match your search or filter criteria.'
                                                            : 'No purchase order lines have been created yet.'}
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
                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">{purchaseOrderLine.line}</span>
                                                        {#if purchaseOrderLine.code}
                                                            <span class="text-xs text-muted-foreground">{purchaseOrderLine.code}</span>
                                                        {/if}
                                                    </div>
                                                </td>
                                                <td>
                                                    {#if purchaseOrderLine.purchase_order}
                                                        <div class="flex flex-col gap-1">
                                                            <span class="text-sm font-medium text-secondary-foreground">{purchaseOrderLine.purchase_order.name}</span>
                                                        </div>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td>
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
                                                <td>
                                                    {#if formatQuantityWithUnit(purchaseOrderLine.product, purchaseOrderLine.quantity)}
                                                        <span class="text-sm font-medium">{formatQuantityWithUnit(purchaseOrderLine.product, purchaseOrderLine.quantity)}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td>
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
                                                                {#if hasPermission('purchase-order-lines.update') && purchaseOrderLine.can_be_updated}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(purchaseOrderLine)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('shippings.index') && purchaseOrderLine.purchase_order?.status === 'confirmed'}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openShippingsDrawer(purchaseOrderLine.purchase_order)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-truck"></i></span>
                                                                            <span class="kt-menu-title">Shippings</span>
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
        </div>
    </div>

    <button style="display:none" data-kt-drawer-toggle="#purchase_order_lines_filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#purchase_order_line_view_drawer" aria-label="Toggle view drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#purchase_order_shippings_drawer" aria-label="Toggle shippings drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedPurchaseOrderLine} />
    <ShippingsDrawer purchaseOrder={selectedPurchaseOrderForShippings} on:shippingsUpdated={handleShippingsDrawerUpdated} />
</MainLayout>
