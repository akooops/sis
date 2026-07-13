<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import EditForm from './EditForm.svelte';
    import SubmitToQcForm from './SubmitToQcForm.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        { title: 'WH Good Receipts', url: route('web.admin.wh-good-receipts.index'), active: false },
        { title: 'Index', url: route('web.admin.wh-good-receipts.index'), active: true },
    ];

    const pageTitle = 'WH Good Receipts';

    let whGoodReceipts = [];
    let search = '';
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let filters = {
        sort_direction: 'desc',
        status: '',
        product_id: '',
        shipping_id: '',
        purchase_order_line_id: '',
    };

    let showEditForm = false;
    let showSubmitToQcForm = false;
    let editingWhGoodReceipt = null;
    let submittingWhGoodReceipt = null;
    let selectedWhGoodReceipt = null;

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'received') return 'kt-badge-success';
        if (status === 'submitted_to_qc') return 'kt-badge-info';
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

    async function fetchWhGoodReceipts() {
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
            if (filters.shipping_id) queryParams.shipping_id = filters.shipping_id;
            if (filters.purchase_order_line_id) queryParams.purchase_order_line_id = filters.purchase_order_line_id;

            const response = await fetch(route('api.v1.admin.wh-good-receipts.index', queryParams), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();
            whGoodReceipts = data.w_h_good_receipts || [];
            pagination = data.pagination || {};

            await tick();
            if (window.KTMenu) window.KTMenu.init();
        } catch (error) {
            console.error('Error fetching WH good receipts:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchWhGoodReceipts();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchWhGoodReceipts();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchWhGoodReceipts();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchWhGoodReceipts();
    }

    function openFiltersDrawer() {
        document.querySelector('[data-kt-drawer-toggle="#wh_good_receipts_filters_drawer"]')?.click();
    }

    function openViewDrawer(receipt) {
        selectedWhGoodReceipt = receipt;
        document.querySelector('[data-kt-drawer-toggle="#wh_good_receipt_view_drawer"]')?.click();
    }

    function handleRowClick(receipt) {
        openViewDrawer(receipt);
    }

    function handleEditFormToggle(receipt = null) {
        showSubmitToQcForm = false;
        submittingWhGoodReceipt = null;
        if (receipt?.id) {
            editingWhGoodReceipt = receipt;
            showEditForm = true;
        } else {
            showEditForm = false;
            editingWhGoodReceipt = null;
        }
    }

    function handleSubmitToQcToggle(receipt = null) {
        showEditForm = false;
        editingWhGoodReceipt = null;
        if (receipt?.id) {
            submittingWhGoodReceipt = receipt;
            showSubmitToQcForm = true;
        } else {
            showSubmitToQcForm = false;
            submittingWhGoodReceipt = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingWhGoodReceipt = null;
        fetchWhGoodReceipts();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingWhGoodReceipt = null;
    }

    function handleSubmitToQcCompleted() {
        showSubmitToQcForm = false;
        submittingWhGoodReceipt = null;
        fetchWhGoodReceipts();
    }

    function handleSubmitToQcCanceled() {
        showSubmitToQcForm = false;
        submittingWhGoodReceipt = null;
    }

    async function handleCancelReceipt(receipt) {
        const label = receipt.code || `#${receipt.id}`;
        if (!confirm(`Are you sure you want to cancel receipt "${label}"?\n\nThis action cannot be undone.`)) return;

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.wh-good-receipts.cancel', { whGoodReceipt: receipt.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Warehouse good receipt cancelled successfully', 'success');
                fetchWhGoodReceipts();
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot cancel receipt:\n';
                Object.entries(data.errors).forEach(([, message]) => {
                    errorMessage += `• ${Array.isArray(message) ? message[0] : message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while cancelling the receipt.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    onMount(() => fetchWhGoodReceipts());
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
                        {#if !showEditForm && !showSubmitToQcForm}
                            <div class="flex items-center gap-2">
                                <SearchBar bind:value={search} placeholder="Search WH good receipts..." debounceMs={500} on:search={handleSearchFromComponent} />
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" on:click={openFiltersDrawer} title="Filter receipts" aria-label="Filter receipts">
                                    <i class="fa-solid fa-filter"></i>
                                </button>
                                <ExportButton
                                    tableData={whGoodReceipts}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'batch', label: 'Batch' },
                                        { key: 'product.name', label: 'Product' },
                                        { key: 'quantity', label: 'Quantity' },
                                        { key: 'status', label: 'Status' },
                                        { key: 'created_at', label: 'Created' },
                                    ]}
                                    filename="wh-good-receipts"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>
                        {:else}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={() => handleEditFormToggle()}>
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {/if}
                    </div>
                </div>

                {#if showEditForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                        <EditForm whGoodReceipt={editingWhGoodReceipt} on:updated={handleFormUpdated} on:documentDeleted={fetchWhGoodReceipts} on:canceled={handleEditFormCanceled} />
                    </div>
                {:else if showSubmitToQcForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                        <SubmitToQcForm whGoodReceipt={submittingWhGoodReceipt} on:submitted={handleSubmitToQcCompleted} on:canceled={handleSubmitToQcCanceled} />
                    </div>
                {:else}
                    <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Code</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Quantity</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Batch</span></th>
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
                                    {:else if whGoodReceipts.length === 0}
                                        <tr>
                                            <td colspan="7" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="fa-solid fa-warehouse text-4xl text-muted-foreground mb-4"></i>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground">No warehouse good receipts match your criteria.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each whGoodReceipts as receipt}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    <span class="text-xs font-medium text-primary">#{receipt.id}</span>
                                                </td>
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    {#if receipt.code}
                                                        <span class="text-sm font-medium text-mono">{receipt.code}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    {#if receipt.product}
                                                        <div class="flex items-center gap-3">
                                                            <img src={receipt.product.image_url} alt={receipt.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                                                            <span class="text-sm font-medium">{receipt.product.name}</span>
                                                        </div>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    {#if formatQuantityWithUnit(receipt.product, receipt.quantity)}
                                                        <span class="text-sm font-medium">{formatQuantityWithUnit(receipt.product, receipt.quantity)}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    {#if receipt.batch}
                                                        <span class="text-sm font-medium text-mono">{receipt.batch}</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(receipt)}>
                                                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(receipt.status)} text-xs font-medium capitalize">
                                                        {formatStatus(receipt.status)}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(receipt)}>
                                                                        <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('wh-good-receipts.update') && receipt.can_be_updated}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(receipt)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('wh-good-receipts.submit-to-qc') && receipt.can_be_submitted_to_qc}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleSubmitToQcToggle(receipt)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-flask"></i></span>
                                                                            <span class="kt-menu-title">Submit to QC</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('wh-good-receipts.cancel') && receipt.can_be_cancelled}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancelReceipt(receipt)}>
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

    <button style="display:none" data-kt-drawer-toggle="#wh_good_receipts_filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#wh_good_receipt_view_drawer" aria-label="Toggle view drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedWhGoodReceipt} />
</MainLayout>
