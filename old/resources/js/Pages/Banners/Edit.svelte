<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let banner;
    export let languages;
    export let translations;
    export let medias;

    // Define breadcrumbs for this banner
    const breadcrumbs = [
        {
            title: 'Banners',
            url: route('admin.banners.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.banners.edit', { banner: banner?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Banner';

    // Form data for basic banner info
    let form = {
        name: banner?.name || '',
        status: banner?.status || 'draft',
        media_option: 'upload',
        file: null,
        media_id: '',
        video: null,
        link_type: banner?.page ? 'page' : 'url',
        url: banner?.url || '',
        page_id: banner?.page?.id || '',
        external: banner?.page ? 0 : 1, // 1 for external URL, 0 for internal page
        remove_video: false // Flag to track if current video should be removed
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

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Banner files state
    let bannerFiles = [];
    let uploadingFiles = false;
    let fileUploadProgress = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            const cta = translations?.cta?.[language.code] || '';
            
            translationForms[language.code] = {
                title: title,
                cta: cta,
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

    // Initialize banner files from existing data
    if (banner?.files && Array.isArray(banner.files)) {
        bannerFiles = banner.files.map(file => ({
            id: file.id,
            name: file.original_name || 'File',
            url: file.url,
            type: file.type.startsWith('image/') ? 'image' : 'video',
            size: file.size
        }));
    }

    // Initialize existing video preview if banner has video
    if (banner?.videoUrl) {
        videoPreview = banner.videoUrl;
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

    // Handle video input change
    function handleVideoChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('video/')) {
            form.video = file;
            form.remove_video = false; // Reset remove flag when new video is uploaded
            // Create video preview
            const videoUrl = URL.createObjectURL(file);
            videoPreview = videoUrl;
        }
    }

    // Clear video
    function clearVideo() {
        form.video = null;
        
        // If we have a current video from database, mark it for removal
        if (banner?.videoUrl && videoPreview === banner.videoUrl) {
            form.remove_video = true;
        }
        
        // If we have a new video preview, revoke the object URL
        if (videoPreview && videoPreview !== banner?.videoUrl) {
            URL.revokeObjectURL(videoPreview);
        }
        
        videoPreview = null;
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

    // Handle basic form submission
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
                } else if (key !== 'file' && key !== 'video') {
                    formData.append(key, form[key]);
                }
            }
        });

        // Add remove_video flag if set
        if (form.remove_video) {
            formData.append('remove_video', '1');
        }

        // Add banner files
        bannerFiles.forEach(file => {
            formData.append('files[]', file.id);
        });

        // Add method override for PATCH
        formData.append('_method', 'PATCH');

        router.post(route('admin.banners.update', { banner: banner.id }), formData, {
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
        formData.append('cta', translationForms[languageCode].cta);

        // Send AJAX request
        fetch(route('admin.banners.update-translation', { banner: banner.id }), {
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

        // Set selected menu if exists
        if (banner?.page) {
            selectedPage = {
                id: banner.page.id,
                text: banner.page.name
            };
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
            <!-- Banner Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Banner</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update banner information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.banners.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Banners
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
                                data-kt-tab-toggle="#banner_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit banner
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
                    <!-- Banner Form Tab -->
                    <div class="grow flex flex-col" id="banner_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
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
                            </form>

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
                                                
                                                <!-- Current Page Badge -->
                                                {#if selectedPage}
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="kt-badge kt-badge-primary">
                                                            <i class="ki-filled ki-files text-xs"></i>
                                                            Last selected Page: {selectedPage.text}
                                                        </span>
                                                    </div>
                                                {/if}
                                                
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
                                                
                                                {#if errors.page_id}
                                                    <p class="text-sm text-destructive">{errors.page_id}</p>
                                                {/if}
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>

                            <!-- Banner Thumbnail Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Banner Thumbnail</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current Thumbnail Display -->
                                        {#if banner?.thumbnailUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Thumbnail</label>
                                                <div class="relative inline-block">
                                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                                        <img 
                                                            src={banner.thumbnailUrl} 
                                                            alt="Current banner thumbnail"
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
                                        <!-- Current Video Display -->
                                        {#if banner?.videoUrl && !form.remove_video}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Video</label>
                                                <div class="relative">
                                                    <video 
                                                        src={banner.videoUrl} 
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
                                            </div>
                                        {/if}

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
                                        {#if videoPreview && videoPreview !== banner?.videoUrl}
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

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.banners.index')}" class="kt-btn kt-btn-outline">
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
                                        Update Banner
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
                                        <!-- Banner Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter banner title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Banner Description -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="cta-{language.id}">
                                                Description <span class="text-destructive">*</span>
                                            </label>
                                            <textarea
                                                id="cta-{language.id}"
                                                class="kt-textarea {translationErrors[language.code]?.cta ? 'kt-textarea-error' : ''}"
                                                placeholder="Enter banner cta"
                                                rows="3"
                                                bind:value={translationForms[language.code].cta}
                                            ></textarea>
                                            {#if translationErrors[language.code]?.cta}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].cta[0]}</p>
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