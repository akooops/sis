<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let grade;
    export let languages;
    export let translations;
    export let medias;

    // Define breadcrumbs for this grade
    const breadcrumbs = [
        {
            title: 'Grades',
            url: route('admin.grades.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.grades.edit', { grade: grade?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Grade';

    // Form data for basic grade info
    let form = {
        name: grade?.name || '',
        slug: grade?.slug || '',
        program_id: grade?.program?.id || '',
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
    let selectedProgram = null;

    // Select2 component references
    let mediaSelectComponent;
    let programSelectComponent;
    let summernoteComponent;

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Grade files state
    let gradeFiles = [];
    let uploadingFiles = false;
    let fileUploadProgress = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            const description = translations?.description?.[language.code] || '';
            const content = translations?.content?.[language.code] || '';
            
            translationForms[language.code] = {
                title: title,
                description: description,
                content: content
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

    // Initialize selected program if grade has one
    if (grade?.program) {
        selectedProgram = {
            id: grade.program.id,
            text: grade.program.name
        };
    }

    // Initialize grade files from existing data
    if (grade?.files && Array.isArray(grade.files)) {
        gradeFiles = grade.files.map(file => {
            let category = 'document';
            if (file.type.startsWith('image/')) {
                category = 'image';
            } else if (file.type.startsWith('video/')) {
                category = 'video';
            }
            
            return {
                id: file.id,
                name: file.original_name || 'File',
                url: file.url,
                type: file.type,
                category: category,
                size: file.size
            };
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

    // Handle program selection
    function handleProgramSelect(event) {
        form.program_id = event.detail.value;
        selectedProgram = event.detail.data;
    }

    // Handle program clear
    function handleProgramClear() {
        form.program_id = '';
        selectedProgram = null;
    }

    // Handle file upload for grade files
    async function handleGradeFileUpload(event) {
        const files = Array.from(event.target.files);
        uploadingFiles = true;
        
        for (const file of files) {
            const fileId = Date.now() + Math.random();
            
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

                        // Determine file category
                        let category = 'document';
                        if (uploadedFile.type.startsWith('image/')) {
                            category = 'image';
                        } else if (uploadedFile.type.startsWith('video/')) {
                            category = 'video';
                        }

                        // Add to grade files array
                        gradeFiles = [...gradeFiles, {
                            id: uploadedFile.id,
                            name: uploadedFile.original_name || file.name,
                            url: uploadedFile.url,
                            type: uploadedFile.type,
                            category: category,
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

    // Remove grade file
    function removeGradeFile(fileId, index) {
        if (confirm('Are you sure you want to remove this file?')) {
            gradeFiles = gradeFiles.filter((_, i) => i !== index);
        }
    }

    // Get file icon based on type
    function getFileIcon(fileType) {
        if (fileType.startsWith('image/')) {
            return 'ki-filled ki-image';
        } else if (fileType.startsWith('video/')) {
            return 'ki-filled ki-video';
        } else if (fileType.includes('pdf')) {
            return 'ki-filled ki-document';
        } else if (fileType.includes('word') || fileType.includes('document')) {
            return 'ki-filled ki-file-word';
        } else if (fileType.includes('excel') || fileType.includes('spreadsheet')) {
            return 'ki-filled ki-file-excel';
        } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
            return 'ki-filled ki-file-powerpoint';
        } else if (fileType.includes('zip') || fileType.includes('rar') || fileType.includes('archive')) {
            return 'ki-filled ki-file-zip';
        } else {
            return 'ki-filled ki-file';
        }
    }

    // Get file badge color
    function getFileBadgeColor(category) {
        switch (category) {
            case 'image':
                return 'kt-badge-primary';
            case 'video':
                return 'kt-badge-success';
            case 'document':
                return 'kt-badge-warning';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get file badge text
    function getFileBadgeText(category) {
        switch (category) {
            case 'image':
                return 'IMG';
            case 'video':
                return 'VID';
            case 'document':
                return 'DOC';
            default:
                return 'FILE';
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

        // Ensure form.content is up-to-date from Summernote
        if (summernoteComponent && summernoteComponent.getValue) {
            form.content = summernoteComponent.getValue();
        }
        
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

        // Add grade files
        gradeFiles.forEach(file => {
            formData.append('files[]', file.id);
        });

        // Always add program_id if it exists (even if empty string for validation)
        if (form.program_id !== null) {
            formData.append('program_id', form.program_id);
        }

        // Add method override for PATCH
        formData.append('_method', 'PATCH');

        router.post(route('admin.grades.update', { grade: grade.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
                if (errors.program_id && programSelectComponent) {
                    programSelectComponent.setError(true);
                }
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
                }
                if (errors.content && summernoteComponent) {
                    summernoteComponent.setError(true);
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
        
        // Get Summernote content
        const summernoteContent = summernoteComponent?.getValue?.() || translationForms[languageCode].content;
        formData.append('content', summernoteContent);

        // Send AJAX request
        fetch(route('admin.grades.update-translation', { grade: grade.id }), {
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
        if (grade?.slug) {
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
            <!-- Grade Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Grade</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update grade information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.grades.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Grades
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
                                data-kt-tab-toggle="#grade_form_tab"
                            >
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit grade
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
                    <!-- Grade Form Tab -->
                    <div class="grow flex flex-col" id="grade_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Basic Information</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Grade Name -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="name">
                                                Grade Name <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="name"
                                                type="text"
                                                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                                placeholder="Enter grade name"
                                                bind:value={form.name}
                                                on:input={handleNameChange}
                                            />
                                            {#if errors.name}
                                                <p class="text-sm text-destructive">{errors.name}</p>
                                            {/if}
                                        </div>

                                        <!-- Grade Slug -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="slug">
                                                Grade Slug <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="slug"
                                                type="text"
                                                class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                                placeholder="Enter grade slug"
                                                bind:value={form.slug}
                                                on:input={handleSlugChange}
                                            />
                                            {#if errors.slug}
                                                <p class="text-sm text-destructive">{errors.slug}</p>
                                            {/if}
                                        </div>

                                        <!-- Grade Program -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="program-select">
                                                Grade Program <span class="text-destructive">*</span>
                                            </label>
                                            {#if selectedProgram}
                                                <!-- Program Badge -->
                                                <div class="flex items-center gap-2">
                                                    <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                        <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                                        Program: {selectedProgram.text}
                                                    </span>
                                                    <button 
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                        on:click={handleProgramClear}
                                                        title="Clear program selection"
                                                    >
                                                        <i class="ki-filled ki-cross text-sm"></i>
                                                    </button>
                                                </div>
                                            {:else}
                                                <!-- Program Filter -->
                                                <Select2
                                                    bind:this={programSelectComponent}
                                                    id="program-select"
                                                    placeholder="Select program..."
                                                    bind:value={form.program_id}
                                                    on:select={handleProgramSelect}
                                                    on:clear={handleProgramClear}
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
                                                                results: data.programs.map(program => ({
                                                                    id: program.id,
                                                                    text: program.name
                                                                }))
                                                            };
                                                        },
                                                        cache: true
                                                    }}
                                                />
                                            {/if}
                                            {#if errors.program_id}
                                                <p class="text-sm text-destructive">{errors.program_id}</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Grade Thumbnail Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Grade Thumbnail</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current Thumbnail Display -->
                                        {#if grade?.thumbnailUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current Thumbnail</label>
                                                <div class="relative inline-block">
                                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                                        <img 
                                                            src={grade.thumbnailUrl} 
                                                            alt="Current grade thumbnail"
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

                            <!-- Grade Files Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Grade Files</h4>
                                    <p class="text-sm text-secondary-foreground">
                                        Upload documents, images, and videos for this grade
                                    </p>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- File Upload Section -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="grade-files">
                                                Upload Files
                                            </label>
                                            <input
                                                id="grade-files"
                                                type="file"
                                                class="kt-input"
                                                accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar"
                                                multiple
                                                on:change={handleGradeFileUpload}
                                                disabled={uploadingFiles}
                                            />
                                            <p class="text-xs text-secondary-foreground">
                                                Supported formats: Images (JPG, PNG, GIF, etc.), Videos (MP4, AVI, MOV, etc.), Documents (PDF, DOC, XLS, PPT, etc.)
                                            </p>
                                            {#if uploadingFiles}
                                                <div class="flex items-center gap-2 text-sm text-primary">
                                                    <i class="ki-outline ki-loading text-base animate-spin"></i>
                                                    Uploading files...
                                                </div>
                                            {/if}
                                        </div>

                                        <!-- Uploaded Files Preview -->
                                        {#if gradeFiles.length > 0}
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-sm font-medium text-mono">Grade Files ({gradeFiles.length})</label>
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive"
                                                        on:click={() => {
                                                            if (confirm('Are you sure you want to remove all files?')) {
                                                                gradeFiles = [];
                                                            }
                                                        }}
                                                    >
                                                        <i class="ki-filled ki-trash text-xs"></i>
                                                        Clear All
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                                                    {#each gradeFiles as file, index}
                                                        <div class="relative group">
                                                            <div class="relative overflow-hidden rounded-lg border bg-muted w-40 h-40 flex items-center justify-center">
                                                                {#if file.category === 'image'}
                                                                    <img 
                                                                        src={file.url} 
                                                                        alt={file.name}
                                                                        class="w-full h-full object-cover"
                                                                    />
                                                                {:else if file.category === 'video'}
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
                                                                {:else}
                                                                    <!-- Document icon placeholder -->
                                                                    <div class="flex flex-col items-center justify-center text-muted-foreground">
                                                                        <i class="{getFileIcon(file.type)} text-4xl mb-2"></i>
                                                                        <span class="text-xs text-center px-2">{file.name}</span>
                                                                    </div>
                                                                {/if}
                                                                
                                                                <!-- Delete button -->
                                                                <button
                                                                    type="button"
                                                                    class="absolute bg-destructive text-white rounded-full flex items-center justify-center hover:bg-destructive/80 transition-colors shadow-sm"
                                                                    style="right: 0.5rem; top: 0.5rem; width: 20px; height: 20px; cursor: pointer;"
                                                                    on:click={() => removeGradeFile(file.id, index)}
                                                                    title="Remove file"
                                                                >
                                                                    <i class="ki-filled ki-cross text-xs"></i>
                                                                </button>
                                                                
                                                                <!-- File type indicator -->
                                                                <div class="absolute bottom-0.5 left-0.5">
                                                                    <span class="kt-badge kt-badge-xs {getFileBadgeColor(file.category)}">
                                                                        {getFileBadgeText(file.category)}
                                                                    </span>
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
                                                <p class="text-sm text-secondary-foreground">No files uploaded to this grade yet.</p>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.grades.index')}" class="kt-btn kt-btn-outline">
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
                                        Update Grade
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
                                        <!-- Grade Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter grade title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Grade Description -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="description-{language.id}">
                                                Description <span class="text-destructive">*</span>
                                            </label>
                                            <textarea
                                                id="description-{language.id}"
                                                class="kt-textarea {translationErrors[language.code]?.description ? 'kt-textarea-error' : ''}"
                                                placeholder="Enter grade description"
                                                rows="3"
                                                bind:value={translationForms[language.code].description}
                                            ></textarea>
                                            {#if translationErrors[language.code]?.description}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].description[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Grade Content -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="summernote-editor-{language.code}">
                                                Content <span class="text-destructive">*</span>
                                            </label>
                                            <Summernote
                                                bind:this={summernoteComponent}
                                                id="summernote-editor-{language.code}"
                                                bind:value={translationForms[language.code].content}
                                                placeholder="Enter grade content"
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