<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let achievement;
    export let languages;
    export let translations;
    export let medias;

    // Define breadcrumbs for this achievement
    const breadcrumbs = [
        {
            title: 'Achievements',
            url: route('admin.achievements.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.achievements.edit', { achievement: achievement?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Achievement';

    // Form data for basic achievement info
    let form = {
        name: achievement?.name || '',
        slug: achievement?.slug || '',
        status: achievement?.status || 'draft',
        achievement_date: achievement?.achievement_date ? new Date(achievement.achievement_date).toISOString().split('T')[0] : '',
        achievement_category_id: achievement?.achievement_category_id || '',
        media_option: 'upload',
        file: null,
        media_id: '',
        external: achievement?.url ? true : false,
        url: achievement?.url || '',
        linkable_type: '',
        linkable_id: ''
    };

    // Pre-fill linkable_type and linkable_id if present
    if (achievement?.linkable_type && achievement?.linkable_id) {
        const typeMap = {
            'App\\Models\\Page': 'Page',
            'App\\Models\\Program': 'Program',
            'App\\Models\\Article': 'Article',
            'App\\Models\\Album': 'Album',
            'App\\Models\\Event': 'Event',
            'App\\Models\\JobPosting': 'JobPosting'
        };
        form.linkable_type = typeMap[achievement.linkable_type] || '';
        form.linkable_id = achievement.linkable_id;
    }

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

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            const description = translations?.description?.[language.code] || '';
            const done_by = translations?.done_by?.[language.code] || '';
            
            translationForms[language.code] = {
                title: title,
                description: description,
                done_by: done_by
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

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
            form.linkable_type = '';
            form.linkable_id = '';
            selectedLinkableItem = null;
        } else {
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

    // Handle basic form submission
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

        // Add method override for PATCH
        formData.append('_method', 'PATCH');

        router.post(route('admin.achievements.update', { achievement: achievement.id }), formData, {
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

    // Handle translation form submission
    function handleTranslationSubmit(languageCode, languageId) {
        translationLoading[languageCode] = true;
        translationErrors[languageCode] = {};

        // Get form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        formData.append('_method', 'PATCH');
        formData.append('language_id', languageId);
        formData.append('title', translationForms[languageCode].title);
        formData.append('description', translationForms[languageCode].description);
        formData.append('done_by', translationForms[languageCode].done_by);

        // Send AJAX request
        fetch(route('admin.achievements.update-translation', { achievement: achievement.id }), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw data;
                });
            }
            return response.json();
        })
        .then(data => {
            // Show success toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: `Translation for ${languageCode} updated successfully.`,
                variant: "success",
                position: "bottom-right",
            });
        })
        .catch(error => {
            // Handle validation errors
            if (error.errors) {
                translationErrors[languageCode] = error.errors;
            } else {
                // General error
                translationErrors[languageCode] = { general: ['An error occurred. Please try again.'] };
            }
        })
        .finally(() => {
            translationLoading[languageCode] = false;
        });
    }

    // Initialize components after mount
    onMount(async () => {
        await tick();
        
        // Set slug manually edited flag if slug was pre-populated
        if (achievement?.slug) {
            slugManuallyEdited = true;
        }
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
                    <h1 class="text-2xl font-bold text-mono">Edit Achievement</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update achievement information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.achievements.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Achievements
                    </a>
                </div>
            </div>

            <!-- Main Content with Tabs -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Language Tabs -->
                    <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                        <div class="flex items-center gap-5">
                            <button 
                                class="kt-tab-toggle py-3 active" 
                                data-kt-tab-toggle="#achievement_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit achievement
                            </button>
                            <button 
                                class="kt-tab-toggle py-3" 
                                data-kt-tab-toggle="#translations_tab"
                            >
                                <i class="ki-filled ki-geolocation text-base me-2"></i>
                                Translations
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <!-- Achievement Form Tab -->
                    <div class="grow flex flex-col" id="achievement_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
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
                                                disabled={achievement?.is_system_achievement}
                                            />
                                            {#if achievement?.is_system_achievement}
                                                <p class="text-sm text-muted-foreground">System achievements cannot have their slug changed</p>
                                            {/if}
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
                            </form>

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

                                        <!-- Badge for selected linkable -->
                                        {#if form.linkable_id && form.linkable_type}
                                            <div class="flex items-center gap-2">
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                                    Linked to: {form.linkable_type} #{form.linkable_id}
                                                </span>
                                                <button 
                                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                    on:click={() => {
                                                        form.linkable_id = '';
                                                        form.linkable_type = '';
                                                        selectedLinkableItem = null;
                                                    }}
                                                    title="Clear linkable"
                                                >
                                                    <i class="ki-filled ki-cross text-sm"></i>
                                                </button>
                                            </div>
                                        {/if}

                                        <!-- External URL Section -->
                                        {#if form.external && !(form.linkable_id && form.linkable_type)}
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
                                        {#if !form.external && !(form.linkable_id && form.linkable_type)}
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

                            <!-- Achievement Thumbnail Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Achievement Thumbnail</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current Thumbnail Display -->
                                        {#if achievement?.thumbnailUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Thumbnail</label>
                                                <div class="relative inline-block">
                                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                                        <img 
                                                            src={achievement.thumbnailUrl} 
                                                            alt="Current achievement thumbnail"
                                                            class="w-32 h-32 object-cover rounded-lg" 
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}

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
                                                    Upload Image
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
                                                        <img 
                                                            src={filePreview} 
                                                            alt="Preview"
                                                            class="w-32 h-32 object-cover rounded-lg border" 
                                                        />
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
                                                    Select Media
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

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.achievements.index')}" class="kt-btn kt-btn-outline">
                                    Cancel
                                </a>
                                <button
                                    type="submit"
                                    class="kt-btn kt-btn-primary"
                                    disabled={loading}
                                    on:click|preventDefault={handleSubmit}
                                >
                                    {#if loading}
                                        <i class="ki-outline ki-loading text-base animate-spin"></i>
                                        Updating...
                                    {:else}
                                        <i class="ki-filled ki-check text-base"></i>
                                        Update Achievement
                                    {/if}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Translations Tab -->
                    <div class="grow flex flex-col hidden" id="translations_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Language Tabs -->
                            <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                                <div class="flex items-center gap-5">
                                    {#each languages as language, index}
                                        <button 
                                            class="kt-tab-toggle py-3 {index === 0 ? 'active' : ''}" 
                                            data-kt-tab-toggle="#translation_tab_{language.code}"
                                        >
                                            <i class="ki-filled ki-translate text-base me-2"></i>
                                            {language.name}
                                        </button>
                                    {/each}
                                </div>
                            </div>

                            <!-- Tab Content -->
                            {#each languages as language, index}
                                <div 
                                    class="grow flex flex-col {index === 0 ? '' : 'hidden'}" 
                                    id="translation_tab_{language.code}"
                                >
                                    <form 
                                        on:submit|preventDefault={() => handleTranslationSubmit(language.code, language.id)}
                                        class="grid gap-4"
                                    >
                                        <!-- Achievement Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter achievement title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Achievement Description -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="description-{language.id}">
                                                Description <span class="text-destructive">*</span>
                                            </label>
                                            <textarea
                                                id="description-{language.id}"
                                                class="kt-textarea {translationErrors[language.code]?.description ? 'kt-textarea-error' : ''}"
                                                placeholder="Enter achievement description"
                                                rows="3"
                                                bind:value={translationForms[language.code].description}
                                            ></textarea>
                                            {#if translationErrors[language.code]?.description}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].description[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Done By -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="done_by-{language.id}">
                                                Done By <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="done_by-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.done_by ? 'kt-input-error' : ''}"
                                                placeholder="Enter who achieved this"
                                                bind:value={translationForms[language.code].done_by}
                                            />
                                            {#if translationErrors[language.code]?.done_by}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].done_by[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Form Actions -->
                                        <div class="flex items-center justify-end gap-3 pt-4">
                                            <button
                                                type="submit"
                                                class="kt-btn kt-btn-success"
                                                disabled={translationLoading[language.code]}
                                            >
                                                {#if translationLoading[language.code]}
                                                    <i class="ki-outline ki-loading text-base animate-spin"></i>
                                                    Saving...
                                                {:else}
                                                    <i class="ki-filled ki-check text-base"></i>
                                                    Save Translation
                                                {/if}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 