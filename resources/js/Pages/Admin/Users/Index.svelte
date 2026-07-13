<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';

    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import UserRolesDrawer from './Roles/UserRolesDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Users',
            url: route('web.admin.users.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('web.admin.users.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Users';

    // Props
    export let search = '';
    
    // Reactive variables
    let users = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    
    // Filter state
    let filters = {
        sort_direction: 'desc'
    };
    
    // View drawer state
    let selectedUser = null;
    
    // Create form state
    let showCreateForm = false;
    
    // Edit form state
    let showEditForm = false;
    let editingUser = null;
    
    // Permissions modal state
    let selectedUserForRoles = null;

    // Fetch users data
    async function fetchUsers() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction
            };
            
            const response = await fetch(route('api.v1.admin.users.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            users = data.users;
            pagination = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            
            // Initialize KT components
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching users:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search from SearchBar component
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchUsers();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchUsers();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchUsers();
    }
    
    // Handle filters change
    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchUsers();
    }
    
    // Open filters drawer
    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Open view drawer
    function openViewDrawer(user) {
        selectedUser = user;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Handle permissions modal toggle
    function openRolesDrawer(user) {
        selectedUserForRoles = user;

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#user_roles_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Handle roles updated
    function handleRolesUpdated(event) {
        // Optionally refresh the users data or show a success message
        toast('User roles updated successfully', 'success');
    }
    
    // Handle row click
    function handleRowClick(user) {
        openViewDrawer(user);
    }
    
    // Handle create user toggle
    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }
    
    // Handle user created
    function handleFormCreated() {
        showCreateForm = false;
        fetchUsers(); // Refresh the table
    }
    
    // Handle form cancel
    function handleFormCanceled() {
        showCreateForm = false;
    }
    
    // Handle edit user toggle
    function handleEditFormToggle(user = null) {
        if (user.id) {
            editingUser = user;
            showEditForm = true;
            showCreateForm = false; // Hide create form if open
        } else {
            showEditForm = false;
            editingUser = null;
        }
    }
    
    // Handle user updated
    function handleFormUpdated() {
        showEditForm = false;
        editingUser = null;
        fetchUsers(); // Refresh the table
    }
    
    // Handle edit form cancel
    function handleEditFormCanceled() {
        showEditForm = false;
        editingUser = null;
    }
    
    // Handle delete user
    async function handleDeleteUser(user) {
        // Show confirmation dialog
        const confirmed = confirm(`Are you sure you want to delete the user "${user.name}"?\n\nThis action cannot be undone and will:\n• Remove all user assignments\n• Users with this user will lose their permissions`);
        
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.users.destroy', user.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Success
                alert('User deleted successfully!');
                fetchUsers(); // Refresh the table
            } else {
                // Handle validation errors (422)
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot delete user:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    alert(errorMessage);
                } else {
                    // Handle other errors
                    alert(data.message || 'An error occurred while deleting the user.');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            alert('Network error occurred. Please try again.');
        }
    }

    onMount(() => {
        fetchUsers();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <!-- Users Table Card -->
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !showCreateForm && !showEditForm}
                            <div class="flex items-center gap-2">
                                <SearchBar 
                                    bind:value={search}
                                    placeholder="Search users..."
                                    debounceMs={500}
                                    showScanner={true}
                                    on:search={handleSearchFromComponent}
                                />
                                
                                <!-- Filter Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-ghost"
                                    on:click={openFiltersDrawer}
                                    title="Filter users"
                                    aria-label="Filter users"
                                >
                                    <i class="fa-solid fa-filter"></i>
                                </button>

                                <ExportButton 
                                    tableData={users}
                                    headers={[
                                        { key: 'id', label: 'ID' },
                                        { key: 'firstname', label: 'First Name' },
                                        { key: 'lastname', label: 'Last Name' },
                                        { key: 'name', label: 'Name' },
                                        { key: 'email', label: 'Email' },
                                        { key: 'phone', label: 'Phone' },
                                        { key: 'created_at', label: 'Created' },
                                        { key: 'updated_at', label: 'Updated' }
                                    ]}
                                    filename="users"
                                    totalRecords={pagination?.total || 0}
                                    currentPerPage={perPage}
                                    {filters}
                                />
                            </div>

                            {#if hasPermission('users.store')}
                                <!-- Add User Button -->
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                    on:click={handleCreateFormToggle}
                                    title="Add new user"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i>
                                        Add User
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
                            user={editingUser}
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
                                                User
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
                                    {:else if users.length === 0}
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
                                        {#each users as user}
                                            <tr class="hover:bg-muted cursor-pointer">
                                                <td on:click={() => handleRowClick(user)}>
                                                    <span class="text-xs font-medium text-primary">#{user.id}</span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-3 max-content">
                                                        <img 
                                                            src={user.avatar_url} 
                                                            alt={user.name}
                                                            class="w-[30px] h-[30px] rounded-lg object-cover"
                                                        />

                                                        <div class="flex flex-col gap-1">
                                                            <span class="text-sm font-medium text-secondary-foreground">
                                                                {user.firstname} {user.lastname}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-xs font-medium text-secondary-foreground">
                                                            <i class="fa-solid fa-user mr-1"></i>
                                                            {user.name}
                                                        </span>

                                                        <span class="text-xs font-medium text-secondary-foreground">
                                                            <i class="fa-solid fa-envelope mr-1"></i>
                                                            {user.email}
                                                        </span>

                                                        {#if user.phone}
                                                            <span class="text-xs font-medium text-secondary-foreground">
                                                                <i class="fa-solid fa-phone mr-1"></i>
                                                                {user.phone}
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
                                                                    <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(user)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="fa-solid fa-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </button>
                                                                </div>
                                                                {#if hasPermission('users.update')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(user)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-pen"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Edit</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('user-roles.index')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openRolesDrawer(user)}>
                                                                            <span class="kt-menu-icon">
                                                                                <i class="fa-solid fa-key"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Roles</span>
                                                                        </button>
                                                                    </div>
                                                                {/if}
                                                                {#if hasPermission('users.destroy')}
                                                                    <div class="kt-menu-item">
                                                                        <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteUser(user)}>
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
    <button style="display:none" data-kt-drawer-toggle="#user_roles_drawer" aria-label="Toggle permissions drawer"></button>

    <!-- Filters Drawer -->
    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    
    <!-- View Drawer -->
    <ViewDrawer {selectedUser} />
    
    <!-- User Permissions Drawer -->
    <UserRolesDrawer 
        user={selectedUserForRoles}
        on:rolesUpdated={handleRolesUpdated}
    />
</MainLayout> 