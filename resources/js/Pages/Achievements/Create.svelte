<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this achievement
    const breadcrumbs = [
        {
            title: 'Achievements',
            url: route('admin.achievements.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.achievements.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Achievement';

    // Form data
    let form = {
        name: '',
        slug: '',
        status: 'draft',
        achievement_date: '',
        achievement_category_id: '',
        media_option: 'upload',
        file: null,
        media_id: '',
        external: false,
        url: '',
        linkable_type: '',
        linkable_id: '',
        title: '',
        description: '',
        done_by: ''
    };

    // Form errors
    let errors = {};

    // File preview
    let filePreview = null;

    // Loading state
    let loading = false;

    // Slug generation flag
    let slugManuallyEdited = false;

    // Dynamic data for selects
    let selectedMedia = null;
    let selectedLinkableItem = null;

    // Select2 component references
    let mediaSelectComponent;
    let categorySelectComponent;
    let linkableIdSelectComponent;

    // Function to convert string to slug
    function stringToSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-')     // Replace spaces with hyphens
            .replace(/-+/g, '-')      // Replace multiple hyphens with single hyphen
            .trim();                  // Trim leading/trailing spaces
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

    // Handle media option change
    function handleMediaOptionChange() {
        if (form.media_option === 'upload') {
            form.media_id = '';
            selectedMedia = null;
        } else {
            form.file = null;
            filePreview = null;
        }
    }

    // Handle media selection
    function handleMediaSelect(event) {
        form.media_id = event.detail.value;
        // Update selected media for preview
        if (event.detail.data) {
            selectedMedia = {
                id: event.detail.data.id,
                name: event.detail.data.text,
                file: { url: event.detail.data.mediaUrl }
            };
        }
    }

    // Handle media clear
    function handleMediaClear() {
        form.media_id = '';
        selectedMedia = null;
    }

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
        form.linkable_id = '';
        selectedLinkableItem = null;
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

    // Handle category selection
    function handleCategorySelect(event) {
        form.achievement_category_id = event.detail.value;
    }

    // Handle category clear
    function handleCategoryClear() {
        form.achievement_category_id = '';
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
                if (key === 'file' && form.file) {
                    formData.append(key, form.file);
                } else if (key === 'external') {
                    formData.append(key, form[key] ? '1' : '0');
                } else if (key === 'linkable_type' && form[key] && modelClassMap[form[key]]) {
                    formData.append(key, modelClassMap[form[key]]);
                } else if (key !== 'file') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.achievements.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
                }
                if (errors.achievement_category_id && categorySelectComponent) {
                    categorySelectComponent.setError(true);
                }
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
            <!-- Achievement Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Achievement</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new achievement to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.achievements.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Achievements
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
                            <!-- Achievement Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Achievement Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter achievement name"
                                    bind:value={form.name}
                                    on:input={handleNameChange}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Achievement Slug -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="slug">
                                    Achievement Slug <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="slug"
                                    type="text"
                                    class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                    placeholder="Enter achievement slug"
                                    bind:value={form.slug}
                                    on:input={handleSlugChange}
                                />
                                {#if errors.slug}
                                    <p class="text-sm text-destructive">{errors.slug}</p>
                                {/if}
                            </div>

                            <!-- Achievement Status -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="status">
                                    Achievement Status <span class="text-destructive">*</span>
                                </label>
                                <select
                                    id="status"
                                    class="kt-select"
                                    bind:value={form.status}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="hidden">Hidden</option>
                                    <option value="published">Published</option>
                                </select>
                                {#if errors.status}
                                    <p class="text-sm text-destructive">{errors.status}</p>
                                {/if}
                            </div>

                            <!-- Achievement Date -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="achievement_date">
                                    Achievement Date <span class="text-destructive">*</span>
                                </label>
                                <Flatpickr
                                    id="achievement_date"
                                    bind:value={form.achievement_date}
                                    placeholder="Select achievement date"
                                    config={{
                                        dateFormat: 'Y-m-d',
                                        maxDate: 'today'
                                    }}
                                />
                                {#if errors.achievement_date}
                                    <p class="text-sm text-destructive">{errors.achievement_date}</p>
                                {/if}
                            </div>

                            <!-- Achievement Category -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="category-select">
                                    Achievement Category <span class="text-destructive">*</span>
                                </label>
                                <Select2
                                    bind:this={categorySelectComponent}
                                    id="category-select"
                                    placeholder="Select achievement category..."
                                    bind:value={form.achievement_category_id}
                                    on:select={handleCategorySelect}
                                    on:clear={handleCategoryClear}
                                    ajax={{
                                        url: route('admin.achievement-categories.index'),
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
                                                results: data.categories.map(category => ({
                                                    id: category.id,
                                                    text: category.name
                                                }))
                                            };
                                        },
                                        cache: true
                                    }}
                                />
                                {#if errors.achievement_category_id}
                                    <p class="text-sm text-destructive">{errors.achievement_category_id}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linkable Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Linkable</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
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

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Achievement Thumbnail</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Media Option Selection -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="media-switch" 
                                    checked={form.media_option === 'select'}
                                    on:change={(e) => {
                                        form.media_option = e.target.checked ? 'select' : 'upload';
                                        handleMediaOptionChange();
                                    }}
                                />
                                <label class="kt-label" for="media-switch">
                                    Select from Media Library
                                </label>
                            </div>

                            <!-- File Upload Section -->
                            {#if form.media_option === 'upload'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="file">
                                        Upload Image <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        id="file"
                                        type="file"
                                        class="kt-input"
                                        accept="image/*"
                                        on:change={handleFileChange}
                                    />
                                    {#if filePreview}
                                        <div class="mt-2">
                                            <img src={filePreview} alt="Preview" class="w-32 h-32 object-cover rounded-lg border" />
                                        </div>
                                    {/if}
                                    {#if errors.file}
                                        <p class="text-sm text-destructive">{errors.file}</p>
                                    {/if}
                                </div>
                            {/if}

                            <!-- Media Select Section -->
                            {#if form.media_option === 'select'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="media-select">
                                        Select Media <span class="text-destructive">*</span>
                                    </label>
                                    <Select2
                                        bind:this={mediaSelectComponent}
                                        id="media-select"
                                        placeholder="Select media..."
                                        bind:value={form.media_id}
                                        on:select={handleMediaSelect}
                                        on:clear={handleMediaClear}
                                        ajax={{
                                            url: route('admin.media.index'),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term,
                                                    type: 'image',
                                                    perPage: 10
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.medias.map(media => ({
                                                        id: media.id,
                                                        text: media.name,
                                                        mediaUrl: media.file?.url || ''
                                                    }))
                                                };
                                            },
                                            cache: true
                                        }}
                                        templateResult={function(data) {
                                            if (data.loading) return data.text;
                                            if (!data.id) return data.text;
                                            
                                            return globalThis.$('<div class="d-flex align-items-center">' +
                                                '<img src="' + data.mediaUrl + '" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">' +
                                                '<span>' + data.text + '</span>' +
                                                '</div>');
                                        }}
                                        templateSelection={function(data) {
                                            if (!data.id) return data.text;
                                            
                                            return globalThis.$('<div class="d-flex flex-column align-items-center">' +
                                                '<img src="' + data.mediaUrl + '" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 3px;">' +
                                                '<span>' + data.text + '</span>' +
                                                '</div>');
                                        }}
                                    />
                                    
                                    <!-- Media Preview -->
                                    {#if form.media_id && selectedMedia}
                                        <div class="mt-2">
                                            <img 
                                                src={selectedMedia.file?.url} 
                                                alt={selectedMedia.name}
                                                class="w-32 h-32 object-cover rounded-lg border" 
                                            />
                                        </div>
                                    {/if}
                                    
                                    {#if errors.media_id}
                                        <p class="text-sm text-destructive">{errors.media_id}</p>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Achievement Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Achievement Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Achievement Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter achievement title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Achievement Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Achievement Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter achievement description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Done By -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="done_by">
                                    Done By <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="done_by"
                                    type="text"
                                    class="kt-input {errors.done_by ? 'kt-input-error' : ''}"
                                    placeholder="Enter who achieved this"
                                    bind:value={form.done_by}
                                />
                                {#if errors.done_by}
                                    <p class="text-sm text-destructive">{errors.done_by}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.achievements.index')}" class="kt-btn kt-btn-outline">
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
                            Create Achievement
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 