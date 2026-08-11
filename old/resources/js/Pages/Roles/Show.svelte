<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { page } from '@inertiajs/svelte';

    // Props
    export let role;

    // Define breadcrumbs for this role
    const breadcrumbs = [
        {
            title: 'Roles',
            url: route('admin.roles.index'),
            active: false
        },
        {
            title: role?.name || 'Role Details',
            url: route('admin.roles.show', { role: role.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Role Details';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Role Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Role Details</h1>
                    <p class="text-sm text-secondary-foreground">
                        View role information and assigned permissions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.roles.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Roles
                    </a>
                    {#if hasPermission('admin.roles.update')}
                    <a href="{route('admin.roles.edit', { role: role.id })}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-pencil text-base"></i>
                        Edit Role
                    </a>
                    {/if}
                </div>
            </div>

            <!-- Role Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Basic Information</h4>
                </div>
                <div class="kt-card-content">
                     <!-- Album Details -->
                     <div class="grid gap-4 w-full">
                        <!-- Role Name -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Role Name</h4>
                            <p class="text-sm text-secondary-foreground">{role?.name}</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Role Default</h4>
                            {#if role.is_default}
                                <span class="kt-badge kt-badge-outline kt-badge-success w-fit">
                                    Default
                                </span>
                            {:else}
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    Not Default
                                </span>
                            {/if}
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Created At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {role?.created_at ? new Date(role.created_at).toLocaleDateString('en-US', {
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
                                {role?.updated_at ? new Date(role.updated_at).toLocaleDateString('en-US', {
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

            <!-- Permissions Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Assigned Permissions</h4>
                    <div class="kt-card-toolbar">
                        <span class="kt-badge kt-badge-outline kt-badge-primary">{role.permissions?.length || 0} permissions</span>
                    </div>
                </div>
                <div class="kt-card-content">
                    {#if role.permissions && role.permissions.length > 0}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {#each role.permissions as permission}
                                <div class="flex items-center gap-3 p-3 border rounded-lg bg-green-50 border-green-200">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div class="flex-1">
                                        <div class="font-medium text-green-800">{permission.name}</div>
                                        {#if permission.description}
                                            <div class="text-sm text-green-600">{permission.description}</div>
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
                            <h3 class="text-lg font-semibold text-mono mb-2">No Permissions Assigned</h3>
                            <p class="text-sm text-secondary-foreground mb-4">
                                This role doesn't have any permissions assigned yet.
                            </p>
                            {#if hasPermission('admin.roles.update')}
                            <a href="{route('admin.roles.edit', { role: role.id })}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus text-base"></i>
                                Assign Permissions
                            </a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>

            <!-- Users with this Role (if available) -->
            {#if role.users && role.users.length > 0}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Users with this Role</h4>
                        <div class="kt-card-toolbar">
                            <span class="kt-badge kt-badge-info">{role.users.length} users</span>
                        </div>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {#each role.users as user}
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