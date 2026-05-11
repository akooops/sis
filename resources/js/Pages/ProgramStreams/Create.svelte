<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let program;
    export let defaultLanguage;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Programs',
            url: route('admin.programs.index'),
            active: false
        },
        {
            title: program?.name || 'Program',
            url: route('admin.program-streams.index', { program: program?.id }),
            active: false
        },
        {
            title: 'Streams',
            url: route('admin.program-streams.index', { program: program?.id }),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.program-streams.create', { program: program?.id }),
            active: true
        }
    ];

    const pageTitle = 'Create Stream';

    // Form data
    let form = {
        name: '',
        slug: '',
        title: '',
        description: '',
        content: ''
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Slug generation flag
    let slugManuallyEdited = false;

    // Summernote editor reference
    let summernoteComponent;

    // Function to convert string to slug
    function stringToSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    // Handle name input change
    function handleNameChange() {
        if (!slugManuallyEdited) {
            form.slug = stringToSlug(form.name);
        }
    }

    // Handle slug input change
    function handleSlugChange() {
        slugManuallyEdited = true;
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;

        // Ensure form.content is up-to-date from Summernote
        if (summernoteComponent && summernoteComponent.getValue) {
            form.content = summernoteComponent.getValue();
        }

        const formData = new FormData();

        Object.keys(form).forEach(key => {
            if (form[key] !== null && form[key] !== '') {
                formData.append(key, form[key]);
            }
        });

        router.post(route('admin.program-streams.store', { program: program.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;

                if (errors.content && summernoteComponent) {
                    summernoteComponent.setError(true);
                }
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
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Stream Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Stream</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new stream to "{program?.name}"
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.program-streams.index', { program: program?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Streams
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
                            <!-- Stream Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Stream Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter stream name"
                                    bind:value={form.name}
                                    on:input={handleNameChange}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Stream Slug -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="slug">
                                    Stream Slug <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="slug"
                                    type="text"
                                    class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                    placeholder="Enter stream slug"
                                    bind:value={form.slug}
                                    on:input={handleSlugChange}
                                />
                                {#if errors.slug}
                                    <p class="text-sm text-destructive">{errors.slug}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Stream Content ({defaultLanguage?.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Stream Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Stream Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter stream title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Stream Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Stream Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter stream description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Stream Content -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="summernote-editor">
                                    Stream Content <span class="text-destructive">*</span>
                                </label>
                                <Summernote
                                    bind:this={summernoteComponent}
                                    id="summernote-editor"
                                    bind:value={form.content}
                                    placeholder="Enter stream content"
                                    height={400}
                                    minHeight={300}
                                    maxHeight={600}
                                    on:change={(event) => {
                                        form.content = event.detail.contents;
                                    }}
                                />
                                {#if errors.content}
                                    <p class="text-sm text-destructive">{errors.content}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.program-streams.index', { program: program?.id })}" class="kt-btn kt-btn-outline">
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
                            Create Stream
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
