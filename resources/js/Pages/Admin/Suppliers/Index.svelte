<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import SupplierProductsDrawer from './Products/SupplierProductsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Suppliers',
            url: route('web.admin.suppliers.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.suppliers.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Suppliers';

    export let search = '';
    
    let suppliers = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    
    let filters = {
        sort_direction: 'desc',
        product_id: '',
    };
    
    let selectedSupplier = null;
    let showCreateForm = false;
    let showEditForm = false;
    let editingSupplier = null;
    let selectedSupplierForProducts = null;

    async function fetchSuppliers() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.product_id) {
                queryParams.product_id = filters.product_id;
            }
            
            const response = await fetch(route('api.v1.admin.suppliers.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            suppliers = data.suppliers;
            pagination = data.pagination;
            
            await tick();
            
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching suppliers:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchSuppliers();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchSuppliers();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchSuppliers();
    }
    
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchSuppliers();
    }
    
    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    function openViewDrawer(supplier) {
        selectedSupplier = supplier;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function openProductsDrawer(supplier) {
        selectedSupplierForProducts = supplier;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#supplier_products_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    function handleProductsUpdated(event) {
        toast('Supplier products updated successfully', 'success');
    }
    
    function handleRowClick(supplier) {
        openViewDrawer(supplier);
    }
    
    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }
    
    function handleFormCreated() {
        showCreateForm = false;
        fetchSuppliers();
    }
    
    function handleFormCanceled() {
        showCreateForm = false;
    }
    
    function handleEditFormToggle(supplier = null) {
        if (supplier.id) {
            editingSupplier = supplier;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingSupplier = null;
        }
    }
    
    function handleFormUpdated() {
        showEditForm = false;
        editingSupplier = null;
        fetchSuppliers();
    }
    
    function handleEditFormCanceled() {
        showEditForm = false;
        editingSupplier = null;
    }
    
    async function handleDeleteSupplier(supplier) {
        const confirmed = confirm(`Are you sure you want to delete the supplier "${supplier.name}"?\n\nThis action cannot be undone.`);
        
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.suppliers.destroy', supplier.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                alert('Supplier deleted successfully!');
                fetchSuppliers();
            } else {
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete supplier:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    alert(data.message || 'An error occurred while deleting the supplier.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchSuppliers();
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
                                    placeholder="Search suppliers..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />
                                
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter suppliers"
                                    aria-label="Filter suppliers"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton 
                                    tableData={suppliers}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'code', label: 'Code' },
                                        { key: 'email', label: 'Email' },
                                        { key: 'phone', label: 'Phone' },
                                        { key: 'city', label: 'City' },
                                        { key: 'country', label: 'Country' },
                                        { key: 'contact_person', label: 'Contact Person' },
                                        { key: 'contact_person_email', label: 'Contact Email' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="suppliers"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('suppliers.store')}
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new supplier"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                        Add Supplier
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
                            supplier={editingSupplier}
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
                                                Supplier
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
                                    {:else if suppliers.length === 0}
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
                                        {#each suppliers as supplier}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(supplier)}>
                                                    <span class="text-xs font-medium text-primary">#{supplier.id}</span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-3 max-content">
                                                        <img 
                                                            src={supplier.logo_url} 
                                                            alt={supplier.name}
                                                            class="w-[30px] h-[30px] rounded-lg object-cover"
                                                        />

                                                        <div class="flex flex-col gap-1">
                                                            <span class="text-sm font-medium text-secondary-foreground">
                                                                {supplier.name}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-xs font-medium text-secondary-foreground">
                                                            <i class="fa-solid fa-envelope mr-1"></i>
                                                            {supplier.email}
                                                        </span>

                                                        {#if supplier.phone}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-phone mr-1"></i>
                                                                {supplier.phone}
                                                            </span>
                                                        {/if}

                                                        {#if supplier.contact_person}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-user mr-1"></i>
                                                                {supplier.contact_person}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(supplier)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('suppliers.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(supplier)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('suppliers.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openProductsDrawer(supplier)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-boxes-stacked"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Products</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('suppliers.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteSupplier(supplier)}>
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
    <button style="display:none" data-kt-drawer-toggle="#supplier_products_drawer" aria-label="Toggle supplier products drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    
    <ViewDrawer {selectedSupplier} />
    
    <SupplierProductsDrawer
        supplier={selectedSupplierForProducts}
        on:productsUpdated={handleProductsUpdated}
    />
</MainLayout>
