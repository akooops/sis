<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import ProductBomDrawer from './Bom/ProductBomDrawer.svelte';
    import DemandForecastDrawer from './Forecasts/DemandForecastDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Products',
            url: route('web.admin.products.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.products.index'),
            active: true
        }
    ];

    const pageTitle = 'Products';

    export let search = '';

    let products = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;

    let filters = {
        sort_direction: 'desc',
        type: '',
        has_shelf_life: '',
        min_current_stock: '',
        max_current_stock: '',
        supplier_id: '',
    };

    let selectedProduct = null;
    let selectedProductForBom = null;
    let selectedProductForForecast = null;
    let showCreateForm = false;
    let showEditForm = false;
    let editingProduct = null;

    function formatProductType(type) {
        if (type === 'raw_material') return 'Raw Material';
        if (type === 'finished_product') return 'Finished Product';
        if (type === 'component') return 'Component';
        return null;
    }

    function getTypeBadgeClass(type) {
        if (type === 'raw_material') return 'kt-badge-info';
        if (type === 'finished_product') return 'kt-badge-success';
        if (type === 'component') return 'kt-badge-warning';
        return 'kt-badge-secondary';
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    function formatStock(product) {
        return formatQuantityWithUnit(product, product.current_stock);
    }

    $: exportProducts = products.map((product) => ({
        ...product,
        type: formatProductType(product.type) || product.type || '',
        is_unit_integer: product.is_unit_integer ? 'Yes' : 'No',
        has_shelf_life: product.has_shelf_life ? 'Yes' : 'No',
        batch_size: formatQuantityWithUnit(product, product.batch_size) || '',
        min_stock: formatQuantityWithUnit(product, product.min_stock) || '',
        max_stock: formatQuantityWithUnit(product, product.max_stock) || '',
        current_stock: formatQuantityWithUnit(product, product.current_stock) || '',
        scrap_rate: product.scrap_rate != null && product.scrap_rate !== '' ? `${product.scrap_rate}%` : '',
    }));

    async function fetchProducts() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.type) queryParams.type = filters.type;
            if (filters.has_shelf_life !== '') queryParams.has_shelf_life = filters.has_shelf_life;
            if (filters.min_current_stock) queryParams.min_current_stock = filters.min_current_stock;
            if (filters.max_current_stock) queryParams.max_current_stock = filters.max_current_stock;
            if (filters.supplier_id) queryParams.supplier_id = filters.supplier_id;

            const response = await fetch(route('api.v1.admin.products.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            products = data.products;
            pagination = data.pagination;

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching products:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchProducts();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchProducts();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchProducts();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchProducts();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openViewDrawer(product) {
        selectedProduct = product;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openBomDrawer(product) {
        selectedProductForBom = product;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#product_bom_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openForecastDrawer(product) {
        selectedProductForForecast = product;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#demand_forecast_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleRowClick(product) {
        openViewDrawer(product);
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchProducts();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(product = null) {
        if (product?.id) {
            editingProduct = product;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingProduct = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingProduct = null;
        fetchProducts();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingProduct = null;
    }

    async function handleDeleteProduct(product) {
        const confirmed = confirm(`Are you sure you want to delete the product "${product.name}"?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.products.destroy', product.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                alert('Product deleted successfully!');
                fetchProducts();
            } else {
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete product:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    alert(data.message || 'An error occurred while deleting the product.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchProducts();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
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
                                    placeholder="Search products..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />

                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter products"
                                    aria-label="Filter products"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton
                                    tableData={exportProducts}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'description', label: 'Description' },
                                        { key: 'type', label: 'Type' },
                                        { key: 'unit_of_measurement', label: 'Unit' },
                                        { key: 'is_unit_integer', label: 'Integer Unit' },
                                        { key: 'supply_lead_time', label: 'Supply Lead Time (days)' },
                                        { key: 'warehouse_goods_receipt_time', label: 'Warehouse Receipt (days)' },
                                        { key: 'qa_inspection_time', label: 'QA Inspection (days)' },
                                        { key: 'qc_inspection_time', label: 'QC Inspection (days)' },
                                        { key: 'qp_inspection_time', label: 'QP Inspection (days)' },
                                        { key: 'batch_size', label: 'Batch Size' },
                                        { key: 'scrap_rate', label: 'Scrap Rate' },
                                        { key: 'has_shelf_life', label: 'Has Shelf Life' },
                                        { key: 'shelf_life_time', label: 'Shelf Life (days)' },
                                        { key: 'min_stock', label: 'Min Stock' },
                                        { key: 'max_stock', label: 'Max Stock' },
                                        { key: 'current_stock', label: 'Current Stock' },
                                        { key: 'preferred_supplier.name', label: 'Preferred Supplier' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' },
                                    ]}
                                    filename="products"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('products.store')}
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new product"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Product
                                </button>
                            {/if}
                        {:else if showCreateForm}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-secondary"
                                on:click={handleCreateFormToggle}
                                title="Cancel"
                            >
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {:else if showEditForm}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-secondary"
                                on:click={handleEditFormToggle}
                                title="Cancel"
                            >
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {/if}
                    </div>
                </div>

                {#if showCreateForm}
                    <div
                        class="kt-card-content p-4"
                        in:fly={{ x: '100%', duration: 750}}
                    >
                        <CreateForm
                            on:created={handleFormCreated}
                            on:canceled={handleFormCanceled}
                        />
                    </div>
                {:else if showEditForm}
                    <div
                        class="kt-card-content p-4"
                        in:fly={{ x: '100%', duration: 750}}
                    >
                        <EditForm
                            product={editingProduct}
                            on:updated={handleFormUpdated}
                            on:canceled={handleEditFormCanceled}
                        />
                    </div>
                {:else}
                    <div
                        class="kt-card-content p-0"
                        in:fly={{ x: '-100%', duration: 750}}
                    >
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;">
                                            <span class="kt-table-col whitespace-nowrap capitalize">ID</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Product</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Type</span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">Current Stock</span>
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
                                                <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                            </tr>
                                        {/each}
                                    {:else if products.length === 0}
                                        <tr>
                                            <td colspan="5" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <div class="mb-4">
                                                        <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                                    </div>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground mb-4">
                                                        {search ? 'No results match your search criteria.' : 'No results have been submitted yet.'}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each products as product}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(product)}>
                                                    <span class="text-xs font-medium text-primary">#{product.id}</span>
                                                </td>
                                                <td on:click={() => handleRowClick(product)}>
                                                    <div class="flex items-center gap-3 max-content">
                                                        <img
                                                            src={product.image_url}
                                                            alt={product.name}
                                                            class="w-[30px] h-[30px] rounded-lg object-cover"
                                                        />
                                                        <div class="flex flex-col gap-1">
                                                            <span class="text-sm font-medium text-secondary-foreground">
                                                                {product.name}
                                                            </span>
                                                            {#if product.code}
                                                                <span class="text-xs text-muted-foreground">{product.code}</span>
                                                            {/if}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td on:click={() => handleRowClick(product)}>
                                                    {#if formatProductType(product.type)}
                                                        <span class="kt-badge kt-badge-outline {getTypeBadgeClass(product.type)} text-xs font-medium capitalize">
                                                            {formatProductType(product.type)}
                                                        </span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(product)}>
                                                    {#if formatStock(product)}
                                                        <span class="text-sm font-medium text-secondary-foreground">
                                                            {formatStock(product)}
                                                        </span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td class="text-center">
                                                    <div class="kt-menu flex-inline" data-kt-menu="true">
                                                        <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                                <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                            </button>
                                                            <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(product)}>
                                                                        <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('product-boms.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openBomDrawer(product)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-sitemap"></i></span>
                                                                            <span class="kt-menu-title">BOM</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if product.type === 'finished_product' && hasPermission('demand-forecasts.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openForecastDrawer(product)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-chart-line"></i></span>
                                                                            <span class="kt-menu-title">Forecasts</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('products.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(product)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('products.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteProduct(product)}>
                                                                            <span class="kt-menu-icon"><i class="fa-solid fa-trash-can"></i></span>
                                                                            <span class="kt-menu-title">Delete</span>
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
                            <Pagination
                                {pagination}
                                {perPage}
                                onPageChange={goToPage}
                                onPerPageChange={handlePerPageChange}
                            />
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    </div>

    <button style="display:none" data-kt-drawer-toggle="#filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#view_drawer" aria-label="Toggle view drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#product_bom_drawer" aria-label="Toggle product BOM drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#demand_forecast_drawer" aria-label="Toggle demand forecast drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedProduct} />
    <ProductBomDrawer product={selectedProductForBom} />
    <DemandForecastDrawer product={selectedProductForForecast} />
</MainLayout>
