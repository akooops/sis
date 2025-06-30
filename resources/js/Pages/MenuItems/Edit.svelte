<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let menuItem;
    export let languages;
    export let translations;
    export let medias;
    export let menu;
    export let defaultLanguage;
    export let linkableItems;

    // Define breadcrumbs for this menuItem
    const breadcrumbs = [
        {
            title: 'Menus Management',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: menuItem?.menu?.name || 'Menu',
            url: route('admin.menu-items.index', { menu: menuItem?.menu.id }),
            active: false
        },
        {
            title: menuItem?.name || 'Edit Menu Item',
            url: route('admin.menu-items.edit', { menuItem: menuItem?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Menu Item';

    // Form data for basic event info
    let form = {
        name: menuItem?.name || '',
        slug: menuItem?.slug || '',
        status: menuItem?.status || 'draft',
        media_option: 'upload',
        file: null,
        media_id: '',
        starts_at: menuItem?.starts_at ? new Date(menuItem.starts_at).toISOString().slice(0, 16).replace('T', ' ') : '',
        ends_at: menuItem?.ends_at ? new Date(menuItem.ends_at).toISOString().slice(0, 16).replace('T', ' ') : '',
        external: menuItem?.external ? true : false,
        url: menuItem?.url || '',
        linkable_type: '',
        linkable_id: '',
        title: menuItem?.title || ''
    };

    // Pre-fill linkable_type and linkable_id if present
    if (menuItem?.linkable_type && menuItem?.linkable_id) {
        // Convert full class name to simple name
        const typeMap = {
            'App\\Models\\Page': 'Page',
            'App\\Models\\Program': 'Program',
            'App\\Models\\Article': 'Article',
            'App\\Models\\Album': 'Album',
            'App\\Models\\Event': 'Event',
            'App\\Models\\Grade': 'Grade',
            'App\\Models\\JobPosting': 'JobPosting'
        };
        form.linkable_type = typeMap[menuItem.linkable_type] || '';
        form.linkable_id = menuItem.linkable_id;
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
    let selectedMenu = null;

    // Select2 component references
    let mediaSelectComponent;
    let linkableIdSelectComponent;

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Selected linkable item for display
    let selectedLinkableItem = null;

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';

            
            translationForms[language.code] = {
                title: title
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
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
        form.linkable_id = ''; // Reset linkable_id when type changes
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

    // Clear linkable (badge clear)
    function clearLinkable() {
        form.linkable_id = '';
        form.linkable_type = '';
        selectedLinkableItem = null;
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

        formData.append('_method', 'PATCH');

        router.post(route('admin.menu-items.update', { menuItem: menuItem.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
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

        // Send AJAX request
        fetch(route('admin.menu-items.update-translation', { menuItem: menuItem.id }), {
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
        if (menuItem?.slug) {
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
            <!-- Menu Item Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Menu Item</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update menu item information
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menu-items.index', { menu: menuItem?.menu.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Menu Items
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
                                data-kt-tab-toggle="#event_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit event
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
                    <!-- Event Form Tab -->
                    <div class="grow flex flex-col" id="event_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
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
                                                id="name"
                                                type="text"
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

                                        <!-- Badge for selected linkable -->
                                        {#if form.linkable_id && form.linkable_type}
                                            <div class="flex items-center gap-2">
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                                    Linked to:  {form.linkable_type} #{form.linkable_id}
                                                </span>
                                                <button 
                                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                    on:click={clearLinkable}
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
                            </form>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.menu-items.index', { menu: menuItem?.menu.id })}" class="kt-btn kt-btn-outline">
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
                                        Update Menu Item
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
                                        <!-- Event Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter event title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
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