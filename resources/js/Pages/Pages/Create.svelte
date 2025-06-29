<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Pages',
            url: '/admin/pages',
            active: false
        },
        {
            title: 'Create',
            url: '/admin/pages/create',
            active: true
        }
    ];
    
    const pageTitle = 'Create Page';

    // Form data
    let form = {
        name: '',
        slug: '',
        status: 'draft',
        menu_id: '',
        media_option: 'upload',
        file: null,
        media_id: '',
        title: '',
        description: '',
        content: ''
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

    // Select2 component references
    let menuSelectComponent;
    let mediaSelectComponent;

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

    // Handle menu selection
    function handleMenuSelect(event) {
        form.menu_id = event.detail.value;
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

    // Handle menu clear
    function handleMenuClear() {
        form.menu_id = '';
    }

    // Handle media clear
    function handleMediaClear() {
        form.media_id = '';
        selectedMedia = null;
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
                } else if (key !== 'file') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post('/admin/pages', formData, {
            onSuccess: () => {
                loading = false;
            },
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.menu_id && menuSelectComponent) {
                    menuSelectComponent.setError(true);
                }
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
                }
            }
        });
    }

    // Initialize components after mount
    onMount(async () => {
        await tick();
        
        // Initialize Summernote editor
        if (globalThis.$ && globalThis.$.fn.summernote) {
            globalThis.$('#summernote-editor').summernote({
                height: 400,
                minHeight: 300,
                maxHeight: 600,
                focus: false,
                codeviewFilter: false,
                codeviewIframeFilter: false,
                disableDragAndDrop: false,
                callbacks: {
                    onChange: function(contents, $editable) {
                        form.content = contents;
                    }
                }
            });
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
            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Page</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new page to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin/pages" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Pages
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
                            <!-- Page Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Page Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter page name"
                                    bind:value={form.name}
                                    on:input={handleNameChange}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Page Slug -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="slug">
                                    Page Slug <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="slug"
                                    type="text"
                                    class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                    placeholder="Enter page slug"
                                    bind:value={form.slug}
                                    on:input={handleSlugChange}
                                />
                                {#if errors.slug}
                                    <p class="text-sm text-destructive">{errors.slug}</p>
                                {/if}
                            </div>

                            <!-- Page Status -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="status">
                                    Page Status <span class="text-destructive">*</span>
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

                            <!-- Menu Selection -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="menu-select">
                                    Add Menu to Page
                                </label>
                                <Select2
                                    bind:this={menuSelectComponent}
                                    id="menu-select"
                                    placeholder="Select menu..."
                                    bind:value={form.menu_id}
                                    on:select={handleMenuSelect}
                                    on:clear={handleMenuClear}
                                    ajax={{
                                        url: route('admin.menus.index'),
                                        dataType: 'json',
                                        delay: 300,
                                        data: function(params) {
                                            return {
                                                search: params.term,
                                                perPage: 50
                                            };
                                        },
                                        processResults: function(data) {
                                            return {
                                                results: data.menus.map(menu => ({
                                                    id: menu.id,
                                                    text: menu.name
                                                }))
                                            };
                                        },
                                        cache: true
                                    }}
                                />
                                {#if errors.menu_id}
                                    <p class="text-sm text-destructive">{errors.menu_id}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Page Thumbnail</h4>
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
                                                    perPage: 50
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
                        <h4 class="kt-card-title">Page Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Page Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Page Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter page title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Page Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Page Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter page description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Page Content -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="summernote-editor">
                                    Page Content <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="summernote-editor"
                                    class="kt-textarea {errors.content ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter page content"
                                    bind:value={form.content}
                                ></textarea>
                                {#if errors.content}
                                    <p class="text-sm text-destructive">{errors.content}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="/admin/pages" class="kt-btn kt-btn-outline">
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
                            Create Page
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 