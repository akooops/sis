<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let album;
    export let languages;
    export let translations;
    export let medias;

    // Define breadcrumbs for this album
    const breadcrumbs = [
        {
            title: 'Albums',
            url: route('admin.albums.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.albums.edit', { album: album?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Album';

    // Form data for basic album info
    let form = {
        name: album?.name || '',
        slug: album?.slug || '',
        status: album?.status || 'draft',
        media_option: 'upload',
        file: null,
        media_id: ''
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
    let selectedMenu = null;

    // Select2 component references
    let mediaSelectComponent;

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Album files state
    let albumFiles = [];
    let uploadingFiles = false;
    let fileUploadProgress = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            const description = translations?.description?.[language.code] || '';
            
            translationForms[language.code] = {
                title: title,
                description: description,
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

    // Initialize album files from existing data
    if (album?.files && Array.isArray(album.files)) {
        albumFiles = album.files.map(file => ({
            id: file.id,
            name: file.original_name || 'File',
            url: file.url,
            type: file.type.startsWith('image/') ? 'image' : 'video',
            size: file.size
        }));
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

    // Handle file upload for album files
    async function handleAlbumFileUpload(event) {
        const files = Array.from(event.target.files);
        uploadingFiles = true;
        
        for (const file of files) {
            // Check if file is image or video
            if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
                continue;
            }
            
            const fileId = Date.now() + Math.random();
            fileUploadProgress[fileId] = 0;
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch(route('admin.files.upload'), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.status === 'success' && result.data && result.data.file) {
                        const uploadedFile = result.data.file;

                        // Add to album files array (use assignment for reactivity)
                        albumFiles = [...albumFiles, {
                            id: uploadedFile.id,
                            name: uploadedFile.original_name || file.name,
                            url: uploadedFile.url,
                            type: uploadedFile.type.startsWith('image/') ? 'image' : 'video',
                            size: uploadedFile.size
                        }];
                    }
                }
            } catch (error) {
                console.error('Error uploading file:', error);
            } finally {
                delete fileUploadProgress[fileId];
            }
        }
        
        uploadingFiles = false;
        event.target.value = ''; // Clear input
    }

    // Remove album file
    function removeAlbumFile(fileId, index) {
        if (confirm('Are you sure you want to remove this file?')) {
            // Remove from album files array (use assignment for reactivity)
            albumFiles = albumFiles.filter((_, i) => i !== index);
        }
    }

    // Format file size
    function formatFileSize(bytes) {
        if (!bytes) return '';
        
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Handle basic form submission
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

        // Add album files
        albumFiles.forEach(file => {
            formData.append('files[]', file.id);
        });

        // Add method override for PATCH
        formData.append('_method', 'PATCH');

        router.post(route('admin.albums.update', { album: album.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
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

        // Send AJAX request
        fetch(route('admin.albums.update-translation', { album: album.id }), {
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
        if (album?.slug) {
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
            <!-- Album Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Album</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update album information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.albums.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Albums
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
                                data-kt-tab-toggle="#album_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit album
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
                    <!-- Album Form Tab -->
                    <div class="grow flex flex-col" id="album_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Basic Information</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Album Name -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="name">
                                                Album Name <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="name"
                                                type="text"
                                                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                                placeholder="Enter album name"
                                                bind:value={form.name}
                                                on:input={handleNameChange}
                                            />
                                            {#if errors.name}
                                                <p class="text-sm text-destructive">{errors.name}</p>
                                            {/if}
                                        </div>

                                        <!-- Album Slug -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="slug">
                                                Album Slug <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="slug"
                                                type="text"
                                                class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                                placeholder="Enter album slug"
                                                bind:value={form.slug}
                                                on:input={handleSlugChange}
                                                disabled={album?.is_system_album}
                                            />
                                            {#if album?.is_system_album}
                                                <p class="text-sm text-muted-foreground">System albums cannot have their slug changed</p>
                                            {/if}
                                            {#if errors.slug}
                                                <p class="text-sm text-destructive">{errors.slug}</p>
                                            {/if}
                                        </div>

                                        <!-- Album Status -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="status">
                                                Album Status <span class="text-destructive">*</span>
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
                                    </div>
                                </div>
                            </form>

                            <!-- Album Thumbnail Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Album Thumbnail</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current Thumbnail Display -->
                                        {#if album?.thumbnailUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Thumbnail</label>
                                                <div class="relative inline-block">
                                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                                        <img 
                                                            src={album.thumbnailUrl} 
                                                            alt="Current album thumbnail"
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

                            <!-- Album Files Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Album Files</h4>
                                    <p class="text-sm text-secondary-foreground">
                                        Upload images and videos for this album
                                    </p>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- File Upload Section -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="album-files">
                                                Upload Files
                                            </label>
                                            <input
                                                id="album-files"
                                                type="file"
                                                class="kt-input"
                                                accept="image/*,video/*"
                                                multiple
                                                on:change={handleAlbumFileUpload}
                                                disabled={uploadingFiles}
                                            />
                                            <p class="text-xs text-secondary-foreground">
                                                Supported formats: Images (JPG, PNG, GIF, etc.) and Videos (MP4, AVI, MOV, etc.)
                                            </p>
                                            {#if uploadingFiles}
                                                <div class="flex items-center gap-2 text-sm text-primary">
                                                    <i class="ki-outline ki-loading text-base animate-spin"></i>
                                                    Uploading files...
                                                </div>
                                            {/if}
                                        </div>

                                        <!-- Uploaded Files Preview -->
                                        {#if albumFiles.length > 0}
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-sm font-medium text-mono">Album Files ({albumFiles.length})</label>
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive"
                                                        on:click={() => {
                                                            if (confirm('Are you sure you want to remove all files?')) {
                                                                albumFiles = [];
                                                            }
                                                        }}
                                                    >
                                                        <i class="ki-filled ki-trash text-xs"></i>
                                                        Clear All
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                                                    {#each albumFiles as file, index}
                                                        <div class="relative group">
                                                            <div class="relative overflow-hidden rounded-lg border bg-muted w-40 h-40">
                                                                {#if file.type === 'image'}
                                                                    <img 
                                                                        src={file.url} 
                                                                        alt={file.name}
                                                                        class="w-full h-full object-cover"
                                                                    />
                                                                {:else}
                                                                    <video 
                                                                        src={file.url}
                                                                        class="w-full h-full object-cover"
                                                                        controls
                                                                        preload="metadata"
                                                                        muted
                                                                    >
                                                                        <source src={file.url} type={file.type}>
                                                                        Your browser does not support the video tag.
                                                                    </video>
                                                                {/if}
                                                                
                                                                <!-- Delete button -->
                                                                <button
                                                                    type="button"
                                                                    class="absolute bg-destructive text-white rounded-full flex items-center justify-center hover:bg-destructive/80 transition-colors shadow-sm"
                                                                    style="right: 0.5rem; top: 0.5rem; width: 20px; height: 20px; cursor: pointer;"
                                                                    on:click={() => removeAlbumFile(file.id, index)}
                                                                    title="Remove file"
                                                                >
                                                                    <i class="ki-filled ki-cross text-xs"></i>
                                                                </button>
                                                                
                                                                <!-- File type indicator -->
                                                                <div class="absolute bottom-0.5 left-0.5">
                                                                    {#if file.type === 'image'}
                                                                        <span class="kt-badge kt-badge-xs kt-badge-primary">IMG</span>
                                                                    {:else}
                                                                        <span class="kt-badge kt-badge-xs kt-badge-success">VID</span>
                                                                    {/if}
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- File name -->
                                                            <p class="text-xs text-secondary-foreground mt-1 truncate" title={file.name}>
                                                                {file.name}
                                                            </p>
                                                            
                                                            <!-- File size -->
                                                            <p class="text-xs text-secondary-foreground">
                                                                {file.size ? formatFileSize(file.size) : ''}
                                                            </p>
                                                        </div>
                                                    {/each}
                                                </div>
                                            </div>
                                        {:else}
                                            <div class="text-center py-8">
                                                <i class="ki-filled ki-folder text-4xl text-secondary-foreground mb-4"></i>
                                                <p class="text-sm text-secondary-foreground">No files uploaded to this album yet.</p>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.albums.index')}" class="kt-btn kt-btn-outline">
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
                                        Update Album
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
                                        <!-- Album Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter album title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Album Description -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="description-{language.id}">
                                                Description <span class="text-destructive">*</span>
                                            </label>
                                            <textarea
                                                id="description-{language.id}"
                                                class="kt-textarea {translationErrors[language.code]?.description ? 'kt-textarea-error' : ''}"
                                                placeholder="Enter album description"
                                                rows="3"
                                                bind:value={translationForms[language.code].description}
                                            ></textarea>
                                            {#if translationErrors[language.code]?.description}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].description[0]}</p>
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