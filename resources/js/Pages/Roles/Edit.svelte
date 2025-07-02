<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let role;
    export let permissions;

    // Define breadcrumbs for this role
    const breadcrumbs = [
        {
            title: 'Roles',
            url: route('admin.roles.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.roles.edit', { role: role?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Role';

    // Form data
    let form = {
        name: role?.name || '',
        is_default: role?.is_default || false,
        permissions: role?.permissions?.map(p => p.id) || []
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Handle permission toggle
    function togglePermission(permissionId) {
        if (form.permissions.includes(permissionId)) {
            form.permissions = form.permissions.filter(id => id !== permissionId);
        } else {
            form.permissions = [...form.permissions, permissionId];
        }
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;
        
        const formData = new FormData();
        
        formData.append('_method', 'PATCH');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        formData.append('name', form.name);
        formData.append('is_default', form.is_default ? 1 : 0);
        
        // Append each permission ID
        form.permissions.forEach(permissionId => {
            formData.append('permissions[]', permissionId);
        });

        router.post(route('admin.roles.update', { role: role.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
        });
    }

    // Initialize components after mount
    onMount(async () => {
        await tick();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Role Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Role</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update role information and permissions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.roles.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Roles
                    </a>
                </div>
            </div>

            <!-- Form -->
            <form on:submit|preventDefault={handleSubmit} class="grid gap-5 lg:gap-7.5">
                <!-- Basic Information Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Basic Information</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Role Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Role Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter role name"
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Role Default -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="is_default" 
                                    checked={form.is_default}
                                    on:change={(e) => {
                                        form.is_default = e.target.checked;
                                    }}
                                />
                                {#if errors.is_default}
                                    <p class="text-sm text-destructive">{errors.is_default}</p>
                                {/if}
                                <label class="kt-label" for="is_default">
                                    Default Role
                                </label>
                            </div> 
                        </div>
                    </div>
                </div>

                <!-- Permissions Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Permissions</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            {#if permissions && permissions.length > 0}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {#each permissions as permission}
                                        <div class="flex items-center gap-3 p-3 border rounded-lg">
                                            <input 
                                                class="kt-switch" 
                                                type="checkbox" 
                                                id="permission_{permission.id}" 
                                                checked={form.permissions.includes(permission.id)}
                                                on:change={() => togglePermission(permission.id)}
                                            />
                                            <label class="kt-label flex-1 cursor-pointer" for="permission_{permission.id}">
                                                <div class="font-medium">{permission.name}</div>
                                                {#if permission.description}
                                                    <div class="text-sm text-secondary-foreground">{permission.description}</div>
                                                {/if}
                                            </label>
                                        </div>
                                    {/each}
                                </div>
                            {:else}
                                <div class="text-center py-8">
                                    <p class="text-secondary-foreground">No permissions available</p>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.roles.index')}" class="kt-btn kt-btn-outline">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="kt-btn kt-btn-primary"
                        disabled={loading}
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Updating...
                        {:else}
                            <i class="ki-filled ki-check text-base"></i>
                            Update Role
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 