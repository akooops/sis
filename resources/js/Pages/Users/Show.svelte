<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { page } from '@inertiajs/svelte';

    // Props
    export let user;

    // Define breadcrumbs for this user
    const breadcrumbs = [
        {
            title: 'Users',
            url: route('admin.users.index'),
            active: false
        },
        {
            title: user?.name || 'User Details',
            url: route('admin.users.show', { user: user.id }),
            active: true
        }
    ];
    
    const pageTitle = 'User Details';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- User Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">User Details</h1>
                    <p class="text-sm text-secondary-foreground">
                        View user information and assigned permissions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.users.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Users
                    </a>
                    {#if hasPermission('admin.users.update')}
                    <a href="{route('admin.users.edit', { user: user.id })}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-pencil text-base"></i>
                        Edit User
                    </a>
                    {/if}
                </div>
            </div>

            <!-- User Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Basic Information</h4>
                </div>
                <div class="kt-card-content">
                     <!-- User Details -->
                     <div class="grid gap-4 w-full">
                        <!-- User Thumbnail -->
                        <div class="flex">
                            <figure class="figure">
                                <img 
                                    src={user?.avatarUrl} 
                                    alt={user?.fullname}
                                    class="rounded-lg w-32 h-32 object-cover"
                                />
                            </figure>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">User Name</h4>
                            <p class="text-sm text-secondary-foreground">@{user?.username}</p>
                        </div>
                        
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">User Name</h4>
                            <p class="text-sm text-secondary-foreground">{user?.fullname}</p>
                        </div>

                        <!-- User Name -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">User email</h4>
                            <p class="text-sm text-secondary-foreground">{user?.email}</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Created At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {user?.created_at ? new Date(user.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A'}
                            </p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Updated At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {user?.updated_at ? new Date(user.updated_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roles Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Assigned Roles</h4>
                    <div class="kt-card-toolbar">
                        <span class="kt-badge kt-badge-outline kt-badge-primary">{user.roles?.length || 0} roles</span>
                    </div>
                </div>
                <div class="kt-card-content">
                    {#if user.roles && user.roles.length > 0}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {#each user.roles as role}
                                <div class="flex items-center gap-3 p-3 border rounded-lg bg-green-50 border-green-200">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div class="flex-1">
                                        <div class="font-medium text-green-800">{role.name}</div>
                                        {#if role.description}
                                            <div class="text-sm text-green-600">{role.description}</div>
                                        {/if}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    {:else}
                        <div class="text-center py-8">
                            <div class="mb-4">
                                <i class="ki-filled ki-shield-cross text-4xl text-muted-foreground"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-mono mb-2">No Roles Assigned</h3>
                            <p class="text-sm text-secondary-foreground mb-4">
                                This user doesn't have any permissions assigned yet.
                            </p>
                            {#if hasPermission('admin.users.update')}
                            <a href="{route('admin.users.edit', { user: user.id })}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus text-base"></i>
                                Assign Roles
                            </a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>

            <!-- Users with this User (if available) -->
            {#if user.users && user.users.length > 0}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Users with this User</h4>
                        <div class="kt-card-toolbar">
                            <span class="kt-badge kt-badge-info">{user.users.length} users</span>
                        </div>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {#each user.users as user}
                                <div class="flex items-center gap-3 p-3 border rounded-lg">
                                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary">
                                            {user.name?.charAt(0) || user.email?.charAt(0) || 'U'}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium">{user.name || 'No Name'}</div>
                                        <div class="text-sm text-secondary-foreground">{user.email}</div>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</AdminLayout> 