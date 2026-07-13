<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import LineProductsDrawer from './Products/LineProductsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Production Lines',
            url: route('web.admin.production-lines.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.production-lines.index'),
            active: true
        }
    ];

    const pageTitle = 'Production Lines';

    export let search = '';

    let productionLines = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;

    let filters = {
        sort_direction: 'desc',
        production_site_id: '',
    };

    let selectedProductionLine = null;
    let showCreateForm = false;
    let showEditForm = false;
    let editingProductionLine = null;
    let selectedProductionLineForProducts = null;

    async function fetchProductionLines() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.production_site_id) {
                queryParams.production_site_id = filters.production_site_id;
            }

            const response = await fetch(route('api.v1.admin.production-lines.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            productionLines = data.production_lines;
            pagination = data.pagination;

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching production lines:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchProductionLines();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchProductionLines();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchProductionLines();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchProductionLines();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openViewDrawer(productionLine) {
        selectedProductionLine = productionLine;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openProductsDrawer(productionLine) {
        selectedProductionLineForProducts = productionLine;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#line_products_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleProductsUpdated(event) {
        toast('Line products updated successfully', 'success');
    }

    function handleRowClick(productionLine) {
        openViewDrawer(productionLine);
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchProductionLines();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(productionLine = null) {
        if (productionLine.id) {
            editingProductionLine = productionLine;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingProductionLine = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingProductionLine = null;
        fetchProductionLines();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingProductionLine = null;
    }

    async function handleDeleteProductionLine(productionLine) {
        const confirmed = confirm(`Are you sure you want to delete the production line "${productionLine.name}"?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.production-lines.destroy', productionLine.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                alert('Production line deleted successfully!');
                fetchProductionLines();
            } else {
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete production line:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    alert(data.message || 'An error occurred while deleting the production line.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchProductionLines();
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
                                    placeholder="Search production lines..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />

                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter production lines"
                                    aria-label="Filter production lines"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton
                                    tableData={productionLines}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'description', label: 'Description' },
                                        { key: 'production_site.name', label: 'Site' },
                                        { key: 'production_process.name', label: 'Process' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="production-lines"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('production-lines.store')}
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new production line"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Production Line
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
                            productionLine={editingProductionLine}
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
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                ID
                                            </span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Line
                                            </span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Site
                                            </span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Process
                                            </span>
                                        </th>
                                        <th class="w-[80px]">
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Actions
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#if loading}
                                        {#each Array(perPage) as _, i}
                                            <tr>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-full h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-full h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-full h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-full h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-8 h-8 rounded"></div>
                                                </td>
                                            </tr>
                                        {/each}
                                    {:else if productionLines.length === 0}
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
                                        {#each productionLines as productionLine}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(productionLine)}>
                                                    <span class="text-xs font-medium text-primary">#{productionLine.id}</span>
                                                </td>
                                                <td on:click={() => handleRowClick(productionLine)}>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">
                                                            {productionLine.name}
                                                        </span>
                                                        {#if productionLine.code}
                                                            <span class="text-xs text-muted-foreground">
                                                                {productionLine.code}
                                                            </span>
                                                        {/if}
                                                    </div>
                                                </td>
                                                <td on:click={() => handleRowClick(productionLine)}>
                                                    {#if productionLine.production_site?.name}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">
                                                            {productionLine.production_site.name}
                                                        </span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                    {/if}
                                                </td>
                                                <td on:click={() => handleRowClick(productionLine)}>
                                                    {#if productionLine.production_process?.name}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">
                                                            {productionLine.production_process.name}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(productionLine)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('production-lines.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(productionLine)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('line-products.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openProductsDrawer(productionLine)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-boxes-stacked"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Products</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-lines.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteProductionLine(productionLine)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-trash-can"></i>
                                                                            </span>
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
    <button style="display:none" data-kt-drawer-toggle="#line_products_drawer" aria-label="Toggle line products drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />

    <ViewDrawer {selectedProductionLine} />

    <LineProductsDrawer
        productionLine={selectedProductionLineForProducts}
        on:productsUpdated={handleProductsUpdated}
    />
</MainLayout>
