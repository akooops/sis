<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';

    // Props from the server
    export let roles;

    // Define breadcrumbs for this user
    const breadcrumbs = [
        {
            title: 'Users',
            url: route('admin.users.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.users.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create User';

    // Form data
    let form = {
        firstname: '',
        lastname: '',
        username: '',
        email: '',
        password: '',
        file: null,
        roles: []
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // File preview
    let filePreview = null;

    // Handle file input change
    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            form.file = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                filePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Handle role toggle
    function toggleRole(roleId) {
        if (form.roles.includes(roleId)) {
            form.roles = form.roles.filter(id => id !== roleId);
        } else {
            form.roles = [...form.roles, roleId];
        }
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;
        
        const formData = new FormData();
        
        // Add form fields
        Object.keys(form).forEach(key => {
            if (form[key] !== null && form[key] !== '') {
                if (key === 'file' && form.file) {
                    formData.append(key, form.file);
                } else if (key === 'roles' && form.roles.length > 0) {
                    // Add each role ID to the roles array
                    form.roles.forEach(roleId => {
                        formData.append('roles[]', roleId);
                    });
                } else if (key !== 'file' && key !== 'roles') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.users.store'), formData, {
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
            <!-- User Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New User</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new user to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.users.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Users
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
                            <!-- First Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="firstname">
                                    First Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="firstname"
                                    type="text"
                                    class="kt-input {errors.firstname ? 'kt-input-error' : ''}"
                                    placeholder="Enter first name"
                                    bind:value={form.firstname}
                                />
                                {#if errors.firstname}
                                    <p class="text-sm text-destructive">{errors.firstname}</p>
                                {/if}
                            </div>

                            <!-- Last Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="lastname">
                                    Last Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="lastname"
                                    type="text"
                                    class="kt-input {errors.lastname ? 'kt-input-error' : ''}"
                                    placeholder="Enter last name"
                                    bind:value={form.lastname}
                                />
                                {#if errors.lastname}
                                    <p class="text-sm text-destructive">{errors.lastname}</p>
                                {/if}
                            </div>

                            <!-- Username -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="username">
                                    Username <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="username"
                                    type="text"
                                    class="kt-input {errors.username ? 'kt-input-error' : ''}"
                                    placeholder="Enter username"
                                    bind:value={form.username}
                                />
                                {#if errors.username}
                                    <p class="text-sm text-destructive">{errors.username}</p>
                                {/if}
                            </div>

                            <!-- Email -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="email">
                                    Email <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    class="kt-input {errors.email ? 'kt-input-error' : ''}"
                                    placeholder="Enter email address"
                                    bind:value={form.email}
                                />
                                {#if errors.email}
                                    <p class="text-sm text-destructive">{errors.email}</p>
                                {/if}
                            </div>

                            <!-- Password -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="password">
                                    Password <span class="text-destructive">*</span>
                                </label>
                                <div class="relative max-w-72" data-kt-toggle-password="true">
                                    <input 
                                        id="password"
                                        type="password" 
                                        class="kt-input pe-10 {errors.password ? 'kt-input-error' : ''}" 
                                        placeholder="Password"
                                        bind:value={form.password}
                                    />
                                    <button
                                        class="kt-btn kt-btn-icon kt-btn-ghost size-6 absolute end-2 top-1/2 -translate-y-1/2"
                                        data-kt-toggle-password-trigger="true"
                                        type="button"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-eye kt-toggle-password-active:hidden"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                                            ></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-eye-off hidden kt-toggle-password-active:block"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"
                                            ></path>
                                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path>
                                            <path
                                                d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"
                                            ></path>
                                            <path d="m2 2 20 20"></path>
                                        </svg>
                                    </button>
                                </div>
                                {#if errors.password}
                                    <p class="text-sm text-destructive">{errors.password}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Picture Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Profile Picture</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- File Upload Section -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="file">
                                    Upload Image
                                </label>
                                <input
                                    id="file"
                                    type="file"
                                    class="kt-input"
                                    accept="image/*"
                                    on:change={handleFileChange}
                                />
                                <p class="text-xs text-secondary-foreground">
                                    Supported formats: JPG, JPEG, PNG
                                </p>
                                {#if filePreview}
                                    <div class="mt-2">
                                        <img src={filePreview} alt="Preview" class="w-32 h-32 object-cover rounded-lg border" />
                                    </div>
                                {/if}
                                {#if errors.file}
                                    <p class="text-sm text-destructive">{errors.file}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Roles Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">User Roles</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            {#if roles && roles.length > 0}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {#each roles as role}
                                        <div class="flex items-center gap-3 p-3 border rounded-lg">
                                            <input 
                                                class="kt-switch" 
                                                type="checkbox" 
                                                id="role_{role.id}" 
                                                checked={form.roles.includes(role.id)}
                                                on:change={() => toggleRole(role.id)}
                                            />
                                            <label class="kt-label flex-1 cursor-pointer" for="role_{role.id}">
                                                <div class="font-medium">{role.name}</div>
                                                {#if role.description}
                                                    <div class="text-sm text-secondary-foreground">{role.description}</div>
                                                {/if}
                                            </label>
                                        </div>
                                    {/each}
                                </div>
                            {:else}
                                <div class="text-center py-8">
                                    <p class="text-secondary-foreground">No roles available</p>
                                </div>
                            {/if}
                            {#if errors.roles}
                                <p class="text-sm text-destructive">{errors.roles}</p>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.users.index')}" class="kt-btn kt-btn-outline">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="kt-btn kt-btn-primary"
                        disabled={loading}
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-plus text-base"></i>
                            Create User
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 