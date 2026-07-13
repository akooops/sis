<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import ApiKeyPermissionsDrawer from './Permissions/ApiKeyPermissionsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Api Keys',
            url: route('web.admin.api-keys.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.api-keys.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Api Keys';

    // Props
    export let search = '';
    
    // Reactive variables
    let apiKeys = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    
    // Filter state
    let filters = {
        is_default: null,
        module_id: null,
        sort_direction: 'desc'
    };
    
    // View drawer state
    let selectedApiKey = null;
    
    // Create form state
    let showCreateForm = false;
    
    // Permissions modal state
    let selectedApiKeyForPermissions = null;

    // Fetch api keys data
    async function fetchApikeys() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                module_id: filters.module_id !== null ? filters.module_id : null,
                is_default: filters.is_default !== null ? filters.is_default : null,
                sort_direction: filters.sort_direction
            };
            
            const response = await fetch(route('api.v1.admin.api-keys.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            apiKeys = data.api_keys;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            
            // Initialize KT components
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching api keys:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search from SearchBar component
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchApikeys();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchApikeys();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchApikeys();
    }
    
    // Handle filters change
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchApikeys();
    }
    
    // Open filters drawer
    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Open view drawer
    function openViewDrawer(apiKey) {
        selectedApiKey = apiKey;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Handle permissions modal toggle
    function openPermissionsDrawer(apiKey) {
        selectedApiKeyForPermissions = apiKey;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#api_key_permissions_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Handle permissions updated
    function handlePermissionsUpdated(event) {
        // Optionally refresh the api keys data or show a success message
        toast('Api Key permissions updated successfully', 'success');
    }
    
    // Handle row click
    function handleRowClick(apiKey) {
        openViewDrawer(apiKey);
    }
    
    // Handle create api key toggle
    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }
    
    // Handle api key created
    function handleFormCreated() {
        showCreateForm = false;
        fetchApikeys(); // Refresh the table
    }
    
    // Handle form cancel
    function handleFormCanceled() {
        showCreateForm = false;
    }
    
    // Handle delete api key
    async function handleDeleteApiKey(apiKey) {
        // Show confirmation dialog
        const confirmed = confirm(`Are you sure you want to delete the api key "${apiKey.name}"?\n\nThis action cannot be undone and will:\n• Remove all api key assignments\n• Users with this api key will lose their permissions`);
        
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.api-keys.destroy', apiKey.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Success
                alert('Api Key deleted successfully!');
                fetchApikeys(); // Refresh the table
            } else {
                // Handle validation errors (422)
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete api key:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    // Handle other errors
                    alert(data.message || 'An error occurred while deleting the api key.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchApikeys();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <!-- Api Keys Table Card -->
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !showCreateForm}
                            <div class="flex items-center gap-2">
                                <SearchBar 
                                    bind:value={search}
                                    placeholder="Search api keys..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />
                                
                                <!-- Filter Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter api keys"
                                    aria-label="Filter api keys"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton 
                                    tableData={apiKeys}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'key', label: 'Key' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="apiKeys"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('api-keys.create')}
                                <!-- Add Api Key Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new api key"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                        Add Api Key
                                    </button>
                            {/if}
                        {:else if showCreateForm}
                            <!-- Cancel Create Form Button -->
                            <button 
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-secondary"
                                on:click={handleCreateFormToggle}
                                title="Cancel"
                            >
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Cancel
                            </button>
                        {/if}
                    </div>
                </div>
                
                <!-- Card Content with Animation -->
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
                                                Api Key
                                            </span>
                                        </th>           
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Public Key
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
                                        <!-- Loading skeleton rows -->
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
                                    {:else if apiKeys.length === 0}
                                        <!-- Empty state -->
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
                                        <!-- Actual data rows -->
                                        {#each apiKeys as apiKey}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(apiKey)}>
                                                    <span class="text-xs font-medium text-primary">#{apiKey.id}</span>
                                                </td>
                                                <td class="word-break">
                                                    <span class="text-sm font-medium text-foreground">
                                                        {apiKey.name}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <code class="text-xs bg-muted px-2 py-1 rounded font-mono">
                                                            {apiKey.key}
                                                        </code>
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(apiKey)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('api-keys.permissions.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openPermissionsDrawer(apiKey)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-key"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Permissions</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('api-keys.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteApiKey(apiKey)}>
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

                        <!-- Pagination -->
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
    <!-- End of Container -->
    
    <!-- Hidden buttons to trigger drawers -->
    <button style="display:none" data-kt-drawer-toggle="#filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#view_drawer" aria-label="Toggle view drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#api_key_permissions_drawer" aria-label="Toggle permissions drawer"></button>

    <!-- Filters Drawer -->
    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    
    <!-- View Drawer -->
    <ViewDrawer {selectedApiKey} />
    
    <!-- Api Key Permissions Drawer -->
    <ApiKeyPermissionsDrawer 
        apiKey={selectedApiKeyForPermissions}
        on:permissionsUpdated={handlePermissionsUpdated}
    />
</MainLayout> 