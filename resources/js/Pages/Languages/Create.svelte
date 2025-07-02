<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this language
    const breadcrumbs = [
        {
            title: 'Languages',
            url: route('admin.languages.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.languages.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Language';

    // Form data
    let form = {
        name: '',
        code: '',
        is_default: false,
        is_rtl: false
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Handle form submission
    function handleSubmit() {
        loading = true;

        const formData = new FormData();
        formData.append('name', form.name);
        formData.append('code', form.code);
        formData.append('is_default', form.is_default ? 1 : 0);
        formData.append('is_rtl', form.is_rtl ? 1 : 0);

        router.post(route('admin.languages.store'), formData, {
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
            <!-- Language Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Language</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new language to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.languages.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Languages
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
                            <!-- Language Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Language Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter language name"
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>
                        
                            <!-- Language Code -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Language Code <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="code"
                                    type="text"
                                    class="kt-input {errors.code ? 'kt-input-error' : ''}"
                                    placeholder="Enter language code"
                                    bind:value={form.code}
                                />
                                {#if errors.code}
                                    <p class="text-sm text-destructive">{errors.code}</p>
                                {/if}
                            </div>

                            <!-- Language Default -->
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
                                    Default Language
                                </label>
                            </div>

                            <!-- Language Right to Left -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="is_rtl" 
                                    checked={form.is_rtl}
                                    on:change={(e) => {
                                        form.is_rtl = e.target.checked;
                                    }}
                                />
                                {#if errors.is_rtl}
                                    <p class="text-sm text-destructive">{errors.is_rtl}</p>
                                {/if}
                                <label class="kt-label" for="is_rtl">
                                    Right to Left
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.languages.index')}" class="kt-btn kt-btn-outline">
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
                            Create Language
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 