<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import RolePermissionsDrawer from './Permissions/RolePermissionsDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Roles',
            url: route('web.admin.roles.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.roles.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Roles';

    // Props
    export let search = '';
    
    // Reactive variables
    let roles = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    
    // Filter state
    let filters = {
        is_default: null,
        sort_direction: 'desc'
    };
    
    // View drawer state
    let selectedRole = null;
    
    // Create form state
    let showCreateForm = false;
    
    // Edit form state
    let showEditForm = false;
    let editingRole = null;
    
    // Permissions modal state
    let selectedRoleForPermissions = null;
    


    // Fetch roles data
    async function fetchRoles() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                is_default: filters.is_default !== null ? filters.is_default : null,
                sort_direction: filters.sort_direction
            };
            
            const response = await fetch(route('api.v1.admin.roles.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            roles = data.roles;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            
            // Initialize KT components
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching roles:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search from SearchBar component
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchRoles();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchRoles();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchRoles();
    }
    
    // Handle filters change
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchRoles();
    }
    
    // Open filters drawer
    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Open view drawer
    function openViewDrawer(role) {
        selectedRole = role;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Handle permissions modal toggle
    function openPermissionsDrawer(role) {
        selectedRoleForPermissions = role;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#role_permissions_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Handle permissions updated
    function handlePermissionsUpdated(event) {
        // Optionally refresh the roles data or show a success message
        toast('Role permissions updated successfully', 'success');
    }
    
    // Handle row click
    function handleRowClick(role) {
        openViewDrawer(role);
    }
    
    // Handle create role toggle
    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }
    
    // Handle role created
    function handleFormCreated() {
        showCreateForm = false;
        fetchRoles(); // Refresh the table
    }
    
    // Handle form cancel
    function handleFormCanceled() {
        showCreateForm = false;
    }
    
    // Handle edit role toggle
    function handleEditFormToggle(role = null) {
        if (role.id) {
            editingRole = role;
            showEditForm = true;
            showCreateForm = false; // Hide create form if open
        } else {
            showEditForm = false;
            editingRole = null;
        }
    }
    
    // Handle role updated
    function handleFormUpdated() {
        showEditForm = false;
        editingRole = null;
        fetchRoles(); // Refresh the table
    }
    
    // Handle edit form cancel
    function handleEditFormCanceled() {
        showEditForm = false;
        editingRole = null;
    }
    
    // Handle delete role
    async function handleDeleteRole(role) {
        // Show confirmation dialog
        const confirmed = confirm(`Are you sure you want to delete the role "${role.name}"?\n\nThis action cannot be undone and will:\n• Remove all role assignments\n• Users with this role will lose their permissions`);
        
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.roles.destroy', role.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Success
                alert('Role deleted successfully!');
                fetchRoles(); // Refresh the table
            } else {
                // Handle validation errors (422)
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete role:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    // Handle other errors
                    alert(data.message || 'An error occurred while deleting the role.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchRoles();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <!-- Roles Table Card -->
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !showCreateForm && !showEditForm}
                            <div class="flex items-center gap-2">
                                <SearchBar 
                                    bind:value={search}
                                    placeholder="Search roles..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />
                                
                                <!-- Filter Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter roles"
                                    aria-label="Filter roles"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton 
                                    tableData={roles}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'is_default', label: 'Default' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="roles"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('roles.store')}
                                <!-- Add Role Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new role"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                        Add Role
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
                        {:else if showEditForm}
                            <!-- Cancel Edit Form Button -->
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
                {:else if showEditForm}
                    <div 
                        class="kt-card-content p-4"
                        in:fly={{ x: '100%', duration: 750}}
                    >
                        <EditForm 
                            role={editingRole}
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
                                                Role
                                            </span>
                                        </th>           
                                        <th>
                                            <span class="kt-table-col whitespace-nowrap capitalize">
                                                Default
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
                                    {:else if roles.length === 0}
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
                                        {#each roles as role}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(role)}>
                                                    <span class="text-xs font-medium text-primary">#{role.id}</span>
                                                </td>
                                                <td class="word-break">
                                                    <span class="text-sm font-medium text-foreground">
                                                        {role.name}
                                                    </span>
                                                </td>
                                                <td>
                                                    {#if role.is_default}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">Default</span>
                                                    {:else}
                                                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium">Not Default</span>
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(role)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('roles.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(role)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('role-permissions.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openPermissionsDrawer(role)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-key"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Permissions</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('roles.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteRole(role)}>
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
    <button style="display:none" data-kt-drawer-toggle="#role_permissions_drawer" aria-label="Toggle permissions drawer"></button>

    <!-- Filters Drawer -->
    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    
    <!-- View Drawer -->
    <ViewDrawer {selectedRole} />
    
    <!-- Role Permissions Drawer -->
    <RolePermissionsDrawer 
        role={selectedRoleForPermissions}
        on:permissionsUpdated={handlePermissionsUpdated}
    />
</MainLayout> 