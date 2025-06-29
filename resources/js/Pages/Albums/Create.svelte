<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this album
    const breadcrumbs = [
        {
            title: 'Albums',
            url: route('admin.albums.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.albums.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Album';

    // Form data
    let form = {
        name: '',
        slug: '',
        status: 'draft',
        media_option: 'upload',
        file: null,
        media_id: '',
        title: '',
        description: '',
        files: [] // Array to store uploaded file IDs
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
    let mediaSelectComponent;

    // Album files state
    let albumFiles = [];
    let uploadingFiles = false;
    let fileUploadProgress = {};

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
                            type: uploadedFile.type.startsWith('image/') ? 'image' : 'video'
                        }];
                        
                        // Add to form files array (use assignment for reactivity)
                        form.files = [...form.files, uploadedFile.id];
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
        // Remove from album files array (use assignment for reactivity)
        albumFiles = albumFiles.filter((_, i) => i !== index);
        
        // Remove from form files array (use assignment for reactivity)
        form.files = form.files.filter(id => id !== fileId);
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
                } else if (key === 'files' && form.files.length > 0) {
                    // Add each file ID to the files array
                    form.files.forEach(fileId => {
                        formData.append('files[]', fileId);
                    });
                } else if (key !== 'file' && key !== 'files') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.albums.store'), formData, {
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
            <!-- Album Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Album</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new album to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.albums.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Albums
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
                                />
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
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Album Thumbnail</h4>
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
                                        <label class="text-sm font-medium text-mono">Uploaded Files ({albumFiles.length})</label>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive"
                                            on:click={() => {
                                                albumFiles = [];
                                                form.files = [];
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
                                            </div>
                                        {/each}
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Album Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Album Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Album Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter album title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Album Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Album Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter album description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>
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
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-plus text-base"></i>
                            Create Album
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 