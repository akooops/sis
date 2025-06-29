<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this banner
    const breadcrumbs = [
        {
            title: 'Banners',
            url: route('admin.banners.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.banners.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Banner';

    // Form data
    let form = {
        name: '',
        status: 'draft',
        media_option: 'upload',
        file: null,
        media_id: '',
        title: '',
        cta: '',
        video: null, // Video file
        link_type: 'url', // 'url' or 'page'
        url: '',
        page_id: '',
        external: 1, // 1 for external URL, 0 for internal page
        files: [] // Array to store uploaded file IDs
    };

    // Form errors
    let errors = {};

    // File preview
    let filePreview = null;
    let videoPreview = null;

    // Loading state
    let loading = false;

    // Dynamic data for selects
    let selectedMedia = null;
    let selectedPage = null;

    // Select2 component references
    let mediaSelectComponent;
    let pageSelectComponent;

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

    // Handle video input change
    function handleVideoChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('video/')) {
            form.video = file;
            // Create video preview
            const videoUrl = URL.createObjectURL(file);
            videoPreview = videoUrl;
        }
    }

    // Clear video
    function clearVideo() {
        form.video = null;
        if (videoPreview) {
            URL.revokeObjectURL(videoPreview);
            videoPreview = null;
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

    // Handle link type change
    function handleLinkTypeChange() {
        if (form.link_type === 'url') {
            form.page_id = '';
            selectedPage = null;
            form.external = 1; // External URL
        } else {
            form.url = '';
            form.external = 0; // Internal page
        }
    }

    // Handle page selection
    function handlePageSelect(event) {
        form.page_id = event.detail.value;
        // Update selected page for preview
        if (event.detail.data) {
            selectedPage = {
                id: event.detail.data.id,
                title: event.detail.data.text,
                slug: event.detail.data.slug
            };
        }
    }

    // Handle page clear
    function handlePageClear() {
        form.page_id = '';
        selectedPage = null;
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
                } else if (key === 'video' && form.video) {
                    formData.append(key, form.video);
                } else if (key === 'files' && form.files.length > 0) {
                    // Add each file ID to the files array
                    form.files.forEach(fileId => {
                        formData.append('files[]', fileId);
                    });
                } else if (key !== 'file' && key !== 'video' && key !== 'files') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.banners.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
                }
                if (errors.page_id && pageSelectComponent) {
                    pageSelectComponent.setError(true);
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
            <!-- Banner Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Banner</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new banner to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.banners.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Banners
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
                            <!-- Banner Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Banner Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter banner name"
                                    bind:value={form.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Link Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Banner Link</h4>
                        <p class="text-sm text-secondary-foreground">
                            Choose where the banner should link to when clicked
                        </p>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Link Type Selection -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="link-type-switch" 
                                    checked={form.external === 0}
                                    on:change={(e) => {
                                        form.external = e.target.checked ? 0 : 1;
                                        form.link_type = e.target.checked ? 'page' : 'url';
                                        handleLinkTypeChange();
                                    }}
                                />
                                <label class="kt-label" for="link-type-switch">
                                    Link to Page
                                </label>
                            </div>

                            <!-- URL Input Section -->
                            {#if form.link_type === 'url'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="url">
                                        Link URL <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        id="url"
                                        type="url"
                                        class="kt-input {errors.url ? 'kt-input-error' : ''}"
                                        placeholder="https://example.com"
                                        bind:value={form.url}
                                    />
                                    {#if errors.url}
                                        <p class="text-sm text-destructive">{errors.url}</p>
                                    {/if}
                                </div>
                            {/if}

                            <!-- Page Select Section -->
                            {#if form.link_type === 'page'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="page-select">
                                        Page <span class="text-destructive">*</span>
                                    </label>
                                    <Select2
                                        bind:this={pageSelectComponent}
                                        id="page-select"
                                        placeholder="Select a page..."
                                        bind:value={form.page_id}
                                        on:select={handlePageSelect}
                                        on:clear={handlePageClear}
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
                                                console.log('Pages API response:', data);
                                                if (!data.pages || !Array.isArray(data.pages)) {
                                                    console.error('Invalid pages data:', data);
                                                    return { results: [] };
                                                }
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
                                    
                                    <!-- Page Preview -->
                                    {#if form.page_id && selectedPage}
                                        <div class="mt-2 p-3 bg-muted/50 rounded-lg">
                                            <p class="text-sm font-medium text-mono">{selectedPage.title}</p>
                                            <p class="text-xs text-secondary-foreground">/{selectedPage.slug}</p>
                                        </div>
                                    {/if}
                                    
                                    {#if errors.page_id}
                                        <p class="text-sm text-destructive">{errors.page_id}</p>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Banner Thumbnail</h4>
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

                <!-- Video Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Banner Video (Optional)</h4>
                        <p class="text-sm text-secondary-foreground">
                            Upload a video to display as background
                        </p>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Video Upload Section -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="video">
                                    Upload Video
                                </label>
                                <input
                                    id="video"
                                    type="file"
                                    class="kt-input"
                                    accept="video/*"
                                    on:change={handleVideoChange}
                                />
                                <p class="text-xs text-secondary-foreground">
                                    Supported formats: MP4, AVI, MOV, WebM (Max 50MB)
                                </p>
                                {#if errors.video}
                                    <p class="text-sm text-destructive">{errors.video}</p>
                                {/if}
                            </div>

                            <!-- Video Preview -->
                            {#if videoPreview}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono">Video Preview</label>
                                    <div class="relative">
                                        <video 
                                            src={videoPreview} 
                                            controls 
                                            class="w-full max-w-md rounded-lg border"
                                            style="max-height: 300px;"
                                        >
                                            Your browser does not support the video tag.
                                        </video>
                                        <button
                                            type="button"
                                            class="absolute bg-destructive text-white rounded-full flex items-center justify-center hover:bg-destructive/80 transition-colors shadow-sm"
                                            style="right: 0.5rem; top: 0.5rem; width: 20px; height: 20px; cursor: pointer;"
                                            on:click={clearVideo}
                                            title="Remove video"
                                        >
                                            <i class="ki-filled ki-cross text-xs"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-secondary-foreground">
                                        Video will be displayed as background on the banner
                                    </p>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Banner Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Banner Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Banner Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter banner title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Banner Call to action -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="cta">
                                    Banner Call to action <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="cta"
                                    class="kt-textarea {errors.cta ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter banner cta"
                                    rows="3"
                                    bind:value={form.cta}
                                ></textarea>
                                {#if errors.cta}
                                    <p class="text-sm text-destructive">{errors.cta}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.banners.index')}" class="kt-btn kt-btn-outline">
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
                            Create Banner
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 