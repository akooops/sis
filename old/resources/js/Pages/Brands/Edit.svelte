<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let brand;
    export let languages;
    export let translations;
    export let medias;

    // Define breadcrumbs for this brand
    const breadcrumbs = [
        {
            title: 'Brands',
            url: route('admin.brands.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.brands.edit', { brand: brand?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Brand';

    // Form data for basic brand info
    let form = {
        name: brand?.name || '',
        slug: brand?.slug || '',
        status: brand?.status || 'draft',
        order: brand?.order ?? 0,
        media_option: 'upload',
        file: null,
        media_id: ''
    };

    // Asset library state
    const assetGroups = ['logos', 'colors', 'fonts', 'guidelines', 'audio', 'images', 'documents'];

    let assets = (brand?.assets || []).map(asset => ({
        id: asset.id,
        file_id: null,
        name: asset.name,
        group: asset.group,
        url: asset.url,
        fileType: asset.fileType,
        fileSize: asset.fileSize
    }));

    let uploadingAssets = false;
    let newAssetGroup = 'documents';

    async function handleAssetUpload(event) {
        const files = Array.from(event.target.files);
        uploadingAssets = true;

        for (const file of files) {
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

                const response = await fetch(route('admin.files.upload'), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                });

                if (response.ok) {
                    const result = await response.json();

                    if (result.status === 'success') {
                        const uploaded = result.data.file;
                        const mime = uploaded.type || '';

                        assets = [...assets, {
                            id: null,
                            file_id: uploaded.id,
                            name: (uploaded.original_name || file.name).replace(/\.[^.]+$/, ''),
                            group: newAssetGroup,
                            url: uploaded.url,
                            fileType: mime.startsWith('image/') ? 'image' : (mime.startsWith('audio/') ? 'audio' : 'document'),
                            fileSize: ''
                        }];
                    }
                }
            } catch (error) {
                console.error('Error uploading asset:', error);
            }
        }

        uploadingAssets = false;
        event.target.value = '';
    }

    function removeAsset(index) {
        if (!confirm('Remove this asset? It will be deleted when you save the brand.')) {
            return;
        }

        assets = assets.filter((_, i) => i !== index);
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

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Summernote editors for translations
    let summernoteEditors = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            const tagline = translations?.tagline?.[language.code] || '';
            const description = translations?.description?.[language.code] || '';
            const content = translations?.content?.[language.code] || '';

            translationForms[language.code] = {
                title: title,
                tagline: tagline,
                description: description,
                content: content
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

        // Asset library payload
        assets.forEach((asset, index) => {
            if (asset.id) {
                formData.append(`assets[${index}][id]`, asset.id);
            }
            if (asset.file_id) {
                formData.append(`assets[${index}][file_id]`, asset.file_id);
            }
            formData.append(`assets[${index}][name]`, asset.name);
            formData.append(`assets[${index}][group]`, asset.group);
            formData.append(`assets[${index}][order]`, index);
        });

        // Add method override for PATCH
        formData.append('_method', 'PATCH');

        router.post(route('admin.brands.update', { brand: brand.id }), formData, {
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
        formData.append('tagline', translationForms[languageCode].tagline);
        formData.append('description', translationForms[languageCode].description);
        
        // Get Summernote content
        const summernoteContent = summernoteEditors[languageCode]?.getValue?.() || translationForms[languageCode].content;
        formData.append('content', summernoteContent);

        // Send AJAX request
        fetch(route('admin.brands.update-translation', { brand: brand.id }), {
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
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Brand Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Brand</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update brand information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.brands.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Brands
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
                                data-kt-tab-toggle="#brand_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit brand
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
                    <!-- Brand Form Tab -->
                    <div class="grow flex flex-col" id="brand_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Basic Information</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Brand Name -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="name">
                                                Brand Name <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="name"
                                                type="text"
                                                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                                placeholder="Enter brand name"
                                                bind:value={form.name}
                                            />
                                            {#if errors.name}
                                                <p class="text-sm text-destructive">{errors.name}</p>
                                            {/if}
                                        </div>

                                        <!-- Slug -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="slug">
                                                Slug <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="slug"
                                                type="text"
                                                class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                                placeholder="e.g. visual-identity"
                                                bind:value={form.slug}
                                                on:input={handleSlugChange}
                                            />
                                            <p class="text-xs text-secondary-foreground">
                                                Public page: /identity/&lt;slug&gt;
                                            </p>
                                            {#if errors.slug}
                                                <p class="text-sm text-destructive">{errors.slug}</p>
                                            {/if}
                                        </div>

REPLACEME_INDENT<!-- Status & Order -->
                                        <div class="grid lg:grid-cols-2 gap-4">
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono" for="status">
                                                    Status <span class="text-destructive">*</span>
                                                </label>
                                                <select id="status" class="kt-select {errors.status ? 'kt-input-error' : ''}" bind:value={form.status}>
                                                    <option value="draft">Draft</option>
                                                    <option value="published">Published</option>
                                                </select>
                                                {#if errors.status}
                                                    <p class="text-sm text-destructive">{errors.status}</p>
                                                {/if}
                                            </div>

                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono" for="order">
                                                    Order
                                                </label>
                                                <input
                                                    id="order"
                                                    type="number"
                                                    min="0"
                                                    class="kt-input {errors.order ? 'kt-input-error' : ''}"
                                                    bind:value={form.order}
                                                />
                                                {#if errors.order}
                                                    <p class="text-sm text-destructive">{errors.order}</p>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Hero Image Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Hero Image</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current Thumbnail Display -->
                                        {#if brand?.thumbnailUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Thumbnail</label>
                                                <div class="relative inline-block">
                                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                                        <img 
                                                            src={brand.thumbnailUrl} 
                                                            alt="Current hero image"
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

                            <!-- Asset Library Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Asset Library</h4>
                                    <div class="kt-card-toolbar flex items-center gap-3">
                                        <select class="kt-select w-40" bind:value={newAssetGroup}>
                                            {#each assetGroups as groupOption}
                                                <option value={groupOption}>{groupOption}</option>
                                            {/each}
                                        </select>
                                        <input
                                            id="asset-upload"
                                            type="file"
                                            class="kt-input max-w-64"
                                            multiple
                                            on:change={handleAssetUpload}
                                            disabled={uploadingAssets}
                                        />
                                    </div>
                                </div>
                                <div class="kt-card-content">
                                    {#if uploadingAssets}
                                        <div class="flex items-center gap-2 mb-4 text-sm text-secondary-foreground">
                                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                                            Uploading files...
                                        </div>
                                    {/if}

                                    {#if assets.length === 0}
                                        <p class="text-sm text-secondary-foreground">
                                            No assets yet. Pick a group and upload files (logos, fonts, guideline PDFs, audio tracks, images...). They are saved when you press "Update Brand".
                                        </p>
                                    {:else}
                                        <div class="kt-scrollable-x-auto">
                                            <table class="kt-table kt-table-border">
                                                <thead>
                                                    <tr>
                                                        <th class="w-[70px]">
                                                            <span class="kt-table-col"><span class="kt-table-col-label">File</span></span>
                                                        </th>
                                                        <th class="min-w-[220px]">
                                                            <span class="kt-table-col"><span class="kt-table-col-label">Display Name</span></span>
                                                        </th>
                                                        <th class="w-[160px]">
                                                            <span class="kt-table-col"><span class="kt-table-col-label">Group</span></span>
                                                        </th>
                                                        <th class="w-[100px]">
                                                            <span class="kt-table-col"><span class="kt-table-col-label">Size</span></span>
                                                        </th>
                                                        <th class="w-[80px]">
                                                            <span class="kt-table-col"><span class="kt-table-col-label">Actions</span></span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {#each assets as asset, index}
                                                        <tr class="hover:bg-muted/50">
                                                            <td>
                                                                {#if asset.fileType === 'image'}
                                                                    <img src={asset.url} alt={asset.name} class="w-10 h-10 rounded-lg object-cover" />
                                                                {:else if asset.fileType === 'audio'}
                                                                    <i class="ki-filled ki-abstract-24 text-2xl text-secondary-foreground"></i>
                                                                {:else}
                                                                    <i class="ki-filled ki-document text-2xl text-secondary-foreground"></i>
                                                                {/if}
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="text"
                                                                    class="kt-input"
                                                                    bind:value={asset.name}
                                                                />
                                                            </td>
                                                            <td>
                                                                <select class="kt-select" bind:value={asset.group}>
                                                                    {#each assetGroups as groupOption}
                                                                        <option value={groupOption}>{groupOption}</option>
                                                                    {/each}
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <span class="text-xs text-secondary-foreground">{asset.fileSize || '—'}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="flex items-center gap-1 justify-center">
                                                                    {#if asset.url}
                                                                        <a href={asset.url} target="_blank" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Open file">
                                                                            <i class="ki-filled ki-exit-right-corner"></i>
                                                                        </a>
                                                                    {/if}
                                                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Remove" on:click={() => removeAsset(index)}>
                                                                        <i class="ki-filled ki-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    {/each}
                                                </tbody>
                                            </table>
                                        </div>
                                    {/if}
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.brands.index')}" class="kt-btn kt-btn-outline">
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
                                        Update Brand
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
                                        <!-- Service Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter service title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Brand Tagline -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="tagline-{language.id}">
                                                Tagline
                                            </label>
                                            <input
                                                id="tagline-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.tagline ? 'kt-input-error' : ''}"
                                                placeholder="Short catchphrase shown under the title"
                                                bind:value={translationForms[language.code].tagline}
                                            />
                                            {#if translationErrors[language.code]?.tagline}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].tagline[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Service Description -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="description-{language.id}">
                                                Description <span class="text-destructive">*</span>
                                            </label>
                                            <textarea
                                                id="description-{language.id}"
                                                class="kt-textarea {translationErrors[language.code]?.description ? 'kt-textarea-error' : ''}"
                                                placeholder="Enter service description"
                                                rows="3"
                                                bind:value={translationForms[language.code].description}
                                            ></textarea>
                                            {#if translationErrors[language.code]?.description}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].description[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Service Content -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="content-{language.id}">
                                                Content <span class="text-destructive">*</span>
                                            </label>
                                            <Summernote
                                                bind:this={summernoteEditors[language.code]}
                                                id="content-{language.id}"
                                                bind:value={translationForms[language.code].content}
                                                placeholder="Enter service content"
                                                height={400}
                                                minHeight={300}
                                                maxHeight={600}
                                                on:change={(event) => {
                                                    translationForms[language.code].content = event.detail.contents;
                                                }}
                                            />
                                            {#if translationErrors[language.code]?.content}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].content[0]}</p>
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