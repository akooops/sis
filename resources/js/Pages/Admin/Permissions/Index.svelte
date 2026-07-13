<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import { onMount, tick } from 'svelte';
    
    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Permissions',
            url: route('web.admin.permissions.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.permissions.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Permissions';

    // Props
    export let search = '';
    
    // Reactive variables
    let permissions = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    
    // Filter state
    let filters = {
        is_api: true,
        is_web: true,
        sort_direction: 'desc'
    };
    
    // View drawer state
    let selectedPermission = null;

    // Fetch permissions data
    async function fetchPermissions() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction
            };
            
            // Only add platform filters if not showing all
            if (!(filters.is_api && filters.is_web)) {
                if (filters.is_api !== undefined) {
                    queryParams.is_api = filters.is_api ? 1 : 0;
                }
                if (filters.is_web !== undefined) {
                    queryParams.is_web = filters.is_web ? 1 : 0;
                }
            }
            
            const response = await fetch(route('api.v1.admin.permissions.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            permissions = data.permissions;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            
            // Initialize KT components
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching permissions:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search from SearchBar component
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchPermissions();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchPermissions();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchPermissions();
    }
    
    // Handle filters change
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchPermissions();
    }
    
    // Open filters drawer
    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Open view drawer
    function openViewDrawer(permission) {
        selectedPermission = permission;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Handle row click
    function handleRowClick(permission) {
        openViewDrawer(permission);
    }

    onMount(() => {
        fetchPermissions();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <!-- Permissions Table Card -->
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center gap-2">
                        <SearchBar 
                            bind:value={search}
                            placeholder="Search permissions..."
                            debounceMs={500}
                            showScanner={true}
                            on:search={handleSearchFromComponent}
                        />
                        
                        <!-- Filter Button -->
                        <button 
                            type="button"
                            class="kt-btn kt-btn-sm kt-btn-ghost"
                            on:click={openFiltersDrawer}
                            title="Filter permissions"
                        >
                            <i class="fa-solid fa-filter"></i>
                        </button>

                        <ExportButton 
                            tableData={permissions}
                            headers={[
                                { key: 'id', label: 'ID' },
                                { key: 'firstname', label: 'First Name' },
                                { key: 'is_web', label: 'Web' },
                                { key: 'is_api', label: 'Api' },
                                { key: 'created_at', label: 'Created' },
                                { key: 'updated_at', label: 'Updated' }
                            ]}
                            filename="permissions"
                            totalRecords={pagination?.total || 0}
                            currentPerPage={perPage}
                            {filters}
                        />  
                    </div>
                </div>
                
                <div class="kt-card-content p-0">
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
                                            Permission
                                        </span>
                                    </th>           
                                    <th>
                                        <span class="kt-table-col whitespace-nowrap capitalize">
                                            Supports
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
                                {:else if permissions.length === 0}
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
                                    {#each permissions as permission}
                                        <tr class="hover:bg-muted cursor-pointer">
                                            <td on:click={() => handleRowClick(permission)}>
                                                <span class="text-xs font-medium text-primary">#{permission.id}</span>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-foreground">
                                                    {permission.name}
                                                </span>
                                            </td>
                                            <td>
                                                {#if permission.is_web}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">Web</span>
                                                {/if}
                                                {#if permission.is_api}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">Api</span>
                                                {/if}
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(permission)}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-search-list"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">View</span>
                                                                </button>
                                                            </div>
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
            </div>
        </div>
    </div>
    <!-- End of Container -->
    
    <!-- Hidden buttons to trigger drawers -->
    <button style="display:none" data-kt-drawer-toggle="#filters_drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#view_drawer"></button>
    
    <!-- Filters Drawer -->
    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    
    <!-- View Drawer -->
    <ViewDrawer {selectedPermission} />
</MainLayout> 