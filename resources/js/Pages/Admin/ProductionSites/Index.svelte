<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import ProductionProcessesDrawer from './Processes/ProductionProcessesDrawer.svelte';
    import SiteResponsiblesDrawer from './Responsibles/SiteResponsiblesDrawer.svelte';
    import SiteProductsDrawer from './Products/SiteProductsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Production Sites',
            url: route('web.admin.production-sites.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.production-sites.index'),
            active: true
        }
    ];

    const pageTitle = 'Production Sites';

    export let search = '';

    let productionSites = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;

    let filters = {
        sort_direction: 'desc',
    };

    let selectedProductionSite = null;
    let showCreateForm = false;
    let showEditForm = false;
    let editingProductionSite = null;
    let selectedProductionSiteForProcesses = null;
    let selectedProductionSiteForResponsibles = null;
    let selectedProductionSiteForProducts = null;

    async function fetchProductionSites() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            const response = await fetch(route('api.v1.admin.production-sites.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            productionSites = data.production_sites;
            pagination = data.pagination;

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching production sites:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchProductionSites();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchProductionSites();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchProductionSites();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchProductionSites();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openViewDrawer(productionSite) {
        selectedProductionSite = productionSite;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openProcessesDrawer(productionSite) {
        selectedProductionSiteForProcesses = productionSite;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#production_processes_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleProcessesUpdated(event) {
        toast('Production processes updated successfully', 'success');
    }

    function openResponsiblesDrawer(productionSite) {
        selectedProductionSiteForResponsibles = productionSite;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#site_responsibles_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleResponsiblesUpdated(event) {
        toast('Notification users updated successfully', 'success');
    }

    function openProductsDrawer(productionSite) {
        selectedProductionSiteForProducts = productionSite;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#site_products_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleProductsUpdated(event) {
        toast('Site products updated successfully', 'success');
    }

    function handleRowClick(productionSite) {
        openViewDrawer(productionSite);
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchProductionSites();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(productionSite = null) {
        if (productionSite.id) {
            editingProductionSite = productionSite;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingProductionSite = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingProductionSite = null;
        fetchProductionSites();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingProductionSite = null;
    }

    async function handleDeleteProductionSite(productionSite) {
        const confirmed = confirm(`Are you sure you want to delete the production site "${productionSite.name}"?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.production-sites.destroy', productionSite.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                alert('Production site deleted successfully!');
                fetchProductionSites();
            } else {
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete production site:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    alert(data.message || 'An error occurred while deleting the production site.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchProductionSites();
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
                                    placeholder="Search production sites..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />

                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter production sites"
                                    aria-label="Filter production sites"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton
                                    tableData={productionSites}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'description', label: 'Description' },
                                        { key: 'city', label: 'City' },
                                        { key: 'country', label: 'Country' },
                                        { key: 'contact_person', label: 'Contact Person' },
                                        { key: 'contact_person_email', label: 'Contact Email' },
                                        { key: 'contact_person_phone', label: 'Contact Phone' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="production-sites"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('production-sites.store')}
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new production site"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                    Add Production Site
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
                            productionSite={editingProductionSite}
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
                                                Site
                                            </span>
                                        </th>
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Contact
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
                                                    <div class="kt-skeleton w-8 h-8 rounded"></div>
                                                </td>
                                            </tr>
                                        {/each}
                                    {:else if productionSites.length === 0}
                                        <tr>
                                            <td colspan="4" class="p-10">
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
                                        {#each productionSites as productionSite}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(productionSite)}>
                                                    <span class="text-xs font-medium text-primary">#{productionSite.id}</span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-3 max-content">
                                                        <img
                                                            src={productionSite.logo_url}
                                                            alt={productionSite.name}
                                                            class="w-[30px] h-[30px] rounded-lg object-cover"
                                                        />

                                                        <div class="flex flex-col gap-1">
                                                            <span class="text-sm font-medium text-secondary-foreground">
                                                                {productionSite.name}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        {#if productionSite.contact_person_email}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-envelope mr-1"></i>
                                                                {productionSite.contact_person_email}
                                                            </span>
                                                        {/if}

                                                        {#if productionSite.contact_person_phone}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-phone mr-1"></i>
                                                                {productionSite.contact_person_phone}
                                                            </span>
                                                        {/if}

                                                        {#if productionSite.contact_person}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-user mr-1"></i>
                                                                {productionSite.contact_person}
                                                            </span>
                                                        {/if}
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    <div class="kt-menu flex-inline" data-kt-menu="true">
                                                        <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                                <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                            </button>
                                                            <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(productionSite)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('production-sites.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(productionSite)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('site-products.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openProductsDrawer(productionSite)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-boxes-stacked"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Products</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-processes.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openProcessesDrawer(productionSite)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-gears"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Processes</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('site-responsibles.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openResponsiblesDrawer(productionSite)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-bell"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Notifications</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('production-sites.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteProductionSite(productionSite)}>
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
    <button style="display:none" data-kt-drawer-toggle="#production_processes_drawer" aria-label="Toggle production processes drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#site_responsibles_drawer" aria-label="Toggle site responsibles drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#site_products_drawer" aria-label="Toggle site products drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />

    <ViewDrawer {selectedProductionSite} />

    <ProductionProcessesDrawer
        productionSite={selectedProductionSiteForProcesses}
        on:processesUpdated={handleProcessesUpdated}
    />

    <SiteResponsiblesDrawer
        productionSite={selectedProductionSiteForResponsibles}
        on:responsiblesUpdated={handleResponsiblesUpdated}
    />

    <SiteProductsDrawer
        productionSite={selectedProductionSiteForProducts}
        on:productsUpdated={handleProductsUpdated}
    />
</MainLayout>
