<script>
    import { createEventDispatcher } from 'svelte';
    import SearchBar from '../../../Shared/Utils/Forms/SearchBar.svelte';
    import Pagination from '../../../Shared/Utils/Pagination.svelte';
    import CreateForm from './CreateForm.svelte';
    import { fly } from 'svelte/transition';

    const dispatch = createEventDispatcher();
    
    // Props
    export let user = null;
    
    // Drawer state
    let userRoles = [];
    let search = '';
    let loading = true;
    let errors = {};
    
    // Table state
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};
    
    // View state
    let showCreateForm = false;
    
    // Load data when modal opens
    $: if (user) {
        fetchRoles();
    }
    
    async function fetchRoles() {
        if (!user) return;
        
        loading = true;
        errors = {};
        
        try {
            // Load current user roles with pagination
            const currentResponse = await fetch(route('api.v1.admin.user-roles.index', { 
                user: user.id,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: 'desc'
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });
            
            if (currentResponse.ok) {
                const currentData = await currentResponse.json();
                userRoles = currentData.user_roles || [];
                pagination = currentData.pagination || {};
            }
            
        } catch (error) {
            console.error('Error loading roles:', error);
            errors = { general: 'Failed to load roles. Please try again.' };
        } finally {
            loading = false;
        }
    }
    
    // Remove role from current list
    async function handleDeleteRoleRole(userRole) {
        // Show confirmation dialog
        const confirmed = confirm('Are you sure you want to remove this role from the user?\n\nThis action cannot be undone.');
        
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.user-roles.destroy', { userRole: userRole.id }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Success - reload roles
                toast('User role removed successfully', 'success');
                fetchRoles();
            } else {
                // Handle validation errors (422)
                if (response.status === 422 && data.errors) {
                    let errorMessage = 'Cannot remove user role:\n';
                    Object.entries(data.errors).forEach(([field, message]) => {
                        errorMessage += `• ${message}\n`;
                    });
                    toast(errorMessage, 'error');
                } else {
                    // Handle other errors
                    toast(data.message || 'An error occurred while removing the user role.', 'error');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }
    
    // Handle search
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
    
    
    // Toggle to add form
    function showAddRoleForm() {
        showCreateForm = true;
    }
    
    // Toggle back to table view
    function showRolesTable() {
        showCreateForm = false;
    }
    
    // Handle form created
    function handleFormCreated() {
        showCreateForm = false;
        fetchRoles(); // Refresh the table
    }
    
    // Handle form canceled
    function handleFormCanceled() {
        showCreateForm = false;
    }
    
</script>

<!-- User Roles Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x" data-kt-drawer="true" data-kt-drawer-container="body" id="user_roles_drawer">
    <!-- Drawer Header -->
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            User Roles:
            {#if user}
                <span class="text-muted-foreground">{user.name}</span>
            {/if}
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    
    <!-- Drawer Content -->
    <div class="kt-card w-full">
        <div class="kt-card-header">
            <div class="kt-card-toolbar flex items-center justify-between w-full">
                {#if showCreateForm}
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-secondary"
                        on:click={showRolesTable}
                    >
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Back
                    </button>
                {:else}
                    <div class="flex items-center justify-between w-full">
                        <SearchBar 
                            bind:value={search}
                            placeholder="Search roles..."
                            debounceMs={500}
                            showScanner={false}
                            on:search={handleSearchFromComponent}
                        />

                        {#if hasPermission('user-roles.store')}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-primary"
                                on:click={showAddRoleForm}
                            >
                                <i class="fa-solid fa-plus mr-1"></i>
                                Add Role
                            </button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
        
        {#if showCreateForm}
            <div 
                class="kt-card-content p-4"
                in:fly={{ x: '100%', duration: 750}}
            >
                <CreateForm 
                    {user}
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
                                        Role
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
                                            <div class="kt-skeleton w-8 h-8 rounded"></div>
                                        </td>
                                    </tr>
                                {/each}
                            {:else if userRoles.length === 0}
                                <!-- Empty state -->
                                <tr>
                                    <td colspan="3" class="p-10">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mb-4">
                                                <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                            </div>
                                            <h3 class="text-lg font-semibold text-mono mb-2">No roles found</h3>
                                            <p class="text-sm text-secondary-foreground mb-4">
                                                {search ? 'No roles match your search criteria.' : 'This user has no roles assigned yet.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            {:else}
                                <!-- Actual data rows -->
                                {#each userRoles as userRole}
                                    <tr class="hover:bg-muted cursor-pointer">
                                        <td>
                                            <a href={route('web.admin.roles.index', { search: userRole.role.id })}>  
                                                <span class="text-xs font-medium text-primary">#{userRole.role.id}</span>
                                            </a>
                                        </td>
                                        <td class="word-break">
                                            <span class="text-sm font-medium text-foreground">
                                                {userRole.role.name}
                                            </span>
                                        </td>                                      
                                        <td class="text-center word-break">
                                            <div class="kt-menu flex-inline" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                        {#if hasPermission('user-roles.destroy')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteRoleRole(userRole)}>
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
