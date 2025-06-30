<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { router } from '@inertiajs/svelte';

    // Define breadcrumbs for this menu
    const breadcrumbs = [
        {
            title: 'Menus',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.menus.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Menu';

    // Form data
    let form = {
        name: ''
    };

    // Form errors
    let errors = {};


    // Loading state
    let loading = false;

    // Handle form submission
    function handleSubmit() {
        loading = true;

        router.post(route('admin.menus.store'), form, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Menu Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Menu</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new menu to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menus.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Menus
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
                            <!-- Menu Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Menu Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter menu name"
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.menus.index')}" class="kt-btn kt-btn-outline">
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
                            Create Menu
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 