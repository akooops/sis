<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let menu;
    export let defaultLanguage;
    export let linkableItems;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Menus Management',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: menu?.name || 'Menu',
            url: route('admin.menu-items.index', { menu: menu?.id }),
            active: false
        },
        {
            title: 'Create Menu Item',
            url: route('admin.menu-items.create', { menu: menu?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Create Menu Item';

    // Form data
    let form = {
        name: '',
        external: false, // boolean for switch
        url: '',
        linkable_type: '',
        linkable_id: '',
        title: ''
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Select2 component references
    let linkableIdSelectComponent;

    // Selected linkable item for display
    let selectedLinkableItem = null;

    // Handle external/internal toggle
    function handleExternalChange() {
        if (form.external) {
            // External URL - clear linkable fields
            form.linkable_type = '';
            form.linkable_id = '';
            selectedLinkableItem = null;
        } else {
            // Internal link - clear URL
            form.url = '';
        }
    }

    // Handle linkable type selection
    async function handleLinkableTypeChange() {
        form.linkable_id = ''; // Reset linkable_id when type changes
        selectedLinkableItem = null;
        
        // Wait for DOM updates to complete
        await tick();
    }

    // Handle linkable item selection
    function handleLinkableItemSelect(event) {
        form.linkable_id = event.detail.value;
        if (event.detail.data) {
            selectedLinkableItem = event.detail.data;
        }
    }

    // Handle linkable item clear
    function handleLinkableItemClear() {
        form.linkable_id = '';
        selectedLinkableItem = null;
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;
        
        const formData = new FormData();
        
        // Convert simple model names to full class names
        const modelClassMap = {
            'Page': 'App\\Models\\Page',
            'Program': 'App\\Models\\Program',
            'Article': 'App\\Models\\Article',
            'Album': 'App\\Models\\Album',
            'Event': 'App\\Models\\Event',
            'Grade': 'App\\Models\\Grade',
            'JobPosting': 'App\\Models\\JobPosting'
        };
        
        // Add form fields
        Object.keys(form).forEach(key => {
            if (form[key] !== null && form[key] !== '') {
                if (key === 'external') {
                    // Convert boolean to string for server
                    formData.append(key, form[key] ? '1' : '0');
                } else if (key === 'linkable_type' && form[key] && modelClassMap[form[key]]) {
                    // Convert simple model name to full class name
                    formData.append(key, modelClassMap[form[key]]);
                } else {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.menu-items.store', { menu: menu.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.linkable_id && linkableIdSelectComponent) {
                    linkableIdSelectComponent.setError(true);
                }
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
            <!-- Menu Item Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Menu Item</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new menu item to "{menu?.name}"
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menu-items.index', { menu: menu?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Menu Items
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
                            <!-- Menu Item Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Menu Item Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter menu item name..."
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Link Type Selection -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="external-switch" 
                                    checked={form.external}
                                    on:change={(e) => {
                                        form.external = e.target.checked;
                                        handleExternalChange();
                                    }}
                                />
                                <label class="kt-label" for="external-switch">
                                    Redirect to a URL
                                </label>
                            </div>

                            <!-- External URL Section -->
                            {#if form.external}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="url">
                                        External URL
                                    </label>
                                    <input
                                        type="url"
                                        id="url"
                                        class="kt-input {errors.url ? 'kt-input-error' : ''}"
                                        placeholder="https://example.com"
                                        bind:value={form.url}
                                    />
                                    {#if errors.url}
                                        <p class="text-sm text-destructive">{errors.url}</p>
                                    {/if}
                                </div>
                            {/if}

                            <!-- Internal Link Section -->
                            {#if !form.external}
                                <div class="grid gap-4">
                                    <!-- Content Type Selection -->
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-medium text-mono" for="linkable-type">
                                            Select Content Type
                                        </label>
                                        <select
                                            id="linkable-type"
                                            class="kt-select {errors.linkable_type ? 'kt-select-error' : ''}"
                                            bind:value={form.linkable_type}
                                            on:change={handleLinkableTypeChange}
                                        >
                                            <option value="">-- Select Type --</option>
                                            <option value="Page">Page</option>
                                            <option value="Program">Program</option>
                                            <option value="Article">Article</option>
                                            <option value="Album">Album</option>
                                            <option value="Event">Event</option>
                                            <option value="Grade">Grade</option>
                                            <option value="JobPosting">Job</option>
                                        </select>
                                        {#if errors.linkable_type}
                                            <p class="text-sm text-destructive">{errors.linkable_type}</p>
                                        {/if}
                                    </div>

                                    <!-- Item Selection -->
                                    {#if form.linkable_type}
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="linkable-id">
                                                Select Item
                                            </label>
                                            
                                            <!-- Page Select2 -->
                                            {#if form.linkable_type === 'Page'}
                                                {#key `page-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-page"
                                                        placeholder="Select page..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.pages.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.pages.map(function(page) {
                                                                        return {
                                                                            id: page.id,
                                                                            text: page.name,
                                                                            slug: page.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Program Select2 -->
                                            {#if form.linkable_type === 'Program'}
                                                {#key `program-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-program"
                                                        placeholder="Select program..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.programs.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.programs.map(function(program) {
                                                                        return {
                                                                            id: program.id,
                                                                            text: program.name,
                                                                            slug: program.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Article Select2 -->
                                            {#if form.linkable_type === 'Article'}
                                                {#key `article-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-article"
                                                        placeholder="Select article..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.articles.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.articles.map(function(article) {
                                                                        return {
                                                                            id: article.id,
                                                                            text: article.name,
                                                                            slug: article.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Album Select2 -->
                                            {#if form.linkable_type === 'Album'}
                                                {#key `album-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-album"
                                                        placeholder="Select album..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.albums.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.albums.map(function(album) {
                                                                        return {
                                                                            id: album.id,
                                                                            text: album.name,
                                                                            slug: album.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Event Select2 -->
                                            {#if form.linkable_type === 'Event'}
                                                {#key `event-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-event"
                                                        placeholder="Select event..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.events.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.events.map(function(event) {
                                                                        return {
                                                                            id: event.id,
                                                                            text: event.name,
                                                                            slug: event.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Grade Select2 -->
                                            {#if form.linkable_type === 'Grade'}
                                                {#key `grade-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-grade"
                                                        placeholder="Select grade..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.grades.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.grades.map(function(grade) {
                                                                        return {
                                                                            id: grade.id,
                                                                            text: grade.name,
                                                                            slug: grade.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}

                                            <!-- Job Select2 -->
                                            {#if form.linkable_type === 'JobPosting'}
                                                {#key `job-${form.linkable_type}`}
                                                    <Select2
                                                        bind:this={linkableIdSelectComponent}
                                                        id="linkable-id-job"
                                                        placeholder="Select job..."
                                                        bind:value={form.linkable_id}
                                                        on:select={handleLinkableItemSelect}
                                                        on:clear={handleLinkableItemClear}
                                                        ajax={{
                                                            url: route('admin.job-postings.index'),
                                                            dataType: 'json',
                                                            delay: 300,
                                                            data: function(params) {
                                                                return {
                                                                    search: params.term,
                                                                    perPage: 10
                                                                };
                                                            },
                                                            processResults: function(data) {
                                                                return {
                                                                    results: data.jobs.map(function(job) {
                                                                        return {
                                                                            id: job.id,
                                                                            text: job.name,
                                                                            slug: job.slug
                                                                        };
                                                                    })
                                                                };
                                                            },
                                                            cache: true
                                                        }}
                                                    />
                                                {/key}
                                            {/if}
                                            
                                            {#if errors.linkable_id}
                                                <p class="text-sm text-destructive">{errors.linkable_id}</p>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Translation Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Translation</h4>
                        <p class="text-sm text-secondary-foreground">
                            Menu item title in {defaultLanguage?.name}
                        </p>
                    </div>
                    <div class="kt-card-content">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-mono" for="title">
                                Menu Item {defaultLanguage?.name} Title <span class="text-destructive">*</span>
                            </label>
                            <input
                                type="text"
                                id="title"
                                class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                placeholder="Enter menu item title..."
                                bind:value={form.title}
                            />
                            {#if errors.title}
                                <p class="text-sm text-destructive">{errors.title}</p>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.menu-items.index', { menu: menu?.id })}" class="kt-btn kt-btn-outline">
                        Cancel
                    </a>
                    <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-check text-base"></i>
                            Create Menu Item
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
