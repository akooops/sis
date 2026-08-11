<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';

    export let defaultLanguage;

    const breadcrumbs = [
        {
            title: 'Nationalities',
            url: route('admin.nationalities.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.nationalities.create'),
            active: true
        }
    ];

    const pageTitle = 'Create Nationality';

    let form = {
        name: '',
        code: '',
        title: ''
    };

    let errors = {};
    let loading = false;

    function handleSubmit() {
        loading = true;

        const formData = new FormData();
        formData.append('name', form.name);
        formData.append('code', form.code);
        formData.append('title', form.title);

        router.post(route('admin.nationalities.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
        });
    }

    onMount(async () => {
        await tick();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Nationality</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new nationality option for job applications
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href={route('admin.nationalities.index')} class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Nationalities
                    </a>
                </div>
            </div>

            <form on:submit|preventDefault={handleSubmit} class="grid gap-5 lg:gap-7.5">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Basic Information</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Nationality Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="e.g. Saudi Arabia"
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="code">
                                    Nationality Code <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="code"
                                    type="text"
                                    maxlength="2"
                                    class="kt-input {errors.code ? 'kt-input-error' : ''}"
                                    placeholder="e.g. SA"
                                    bind:value={form.code}
                                />
                                {#if errors.code}
                                    <p class="text-sm text-destructive">{errors.code}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Default Translation ({defaultLanguage?.name || 'Default'})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Nationality Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter nationality title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href={route('admin.nationalities.index')} class="kt-btn kt-btn-outline">
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
                            Create Nationality
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
