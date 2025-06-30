<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this grade
    const breadcrumbs = [
        {
            title: 'Grades',
            url: route('admin.grades.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.grades.create'),
            active: true
        }
    ];
    
    // Update breadcrumbs if program is pre-selected
    $: if (selectedProgram) {
        breadcrumbs[0].url = route('admin.grades.index', { program_id: selectedProgram.id, program_name: selectedProgram.text });
    }
    
    const pageTitle = 'Create Grade';

    // Form data
    let form = {
        name: '',
        slug: '',
        program_id: '',
        media_option: 'upload',
        file: null,
        media_id: '',
        title: '',
        description: '',
        content: '',
        files: []
    };

    // Form errors
    let errors = {};

    // File preview
    let filePreview = null;

    // Loading state
    let loading = false;
    let uploadingFiles = false;

    // Slug generation flag
    let slugManuallyEdited = false;

    // Dynamic data for selects
    let selectedMedia = null;
    let selectedProgram = null;

    // Files array for preview
    let gradeFiles = [];

    // Select2 component references
    let mediaSelectComponent;
    let programSelectComponent;
    let summernoteComponent;

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

    // Handle grade file upload
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

                        // Add to grade files array for preview
                        gradeFiles = [...gradeFiles, {
                            id: uploadedFile.id,
                            name: uploadedFile.original_name || file.name,
                            url: uploadedFile.url,
                            type: uploadedFile.type,
                            category: category
                        }];
                        
                        // Add file ID to form files array
                        form.files = [...form.files, uploadedFile.id];
                    }
                }
            } catch (error) {
                console.error('Error uploading file:', error);
            }
        }
        
        uploadingFiles = false;
        event.target.value = ''; // Clear input
    }

    // Remove grade file
    function removeGradeFile(fileId, index) {
        gradeFiles = gradeFiles.filter(file => file.id !== fileId);
        form.files = form.files.filter(id => id !== fileId);
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

    // Handle form submission
    function handleSubmit() {
        loading = true;

        // Ensure form.content is up-to-date from Summernote
        if (summernoteComponent && summernoteComponent.getValue) {
            form.content = summernoteComponent.getValue();
        }
        
        const formData = new FormData();
        
        // Add form fields
        Object.keys(form).forEach(key => {
            if (key === 'file' && form.file) {
                formData.append(key, form.file);
            } else if (key === 'files' && form.files.length > 0) {
                // Add each file ID to the files array
                form.files.forEach(fileId => {
                    formData.append('files[]', fileId);
                });
            } else if (key !== 'file' && key !== 'files' && form[key] !== null && form[key] !== '') {
                formData.append(key, form[key]);
            }
        });

        // Always add program_id if it exists (even if empty string for validation)
        if (form.program_id !== null) {
            formData.append('program_id', form.program_id);
        }

        router.post(route('admin.grades.store'), formData, {
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

    // Initialize components after mount
    onMount(async () => {
        // Check if program_id is passed in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const initialProgramId = urlParams.get('program_id');
        const initialProgramName = urlParams.get('program_name');
        
        if (initialProgramId) {
            form.program_id = initialProgramId;
            selectedProgram = {
                id: initialProgramId,
                text: initialProgramName || `Program #${initialProgramId}`
            };
        }
        
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
            <!-- Grade Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Grade</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new grade to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.grades.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Grades
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
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Grade Thumbnail</h4>
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
                                        <label class="text-sm font-medium text-mono">Uploaded Files ({gradeFiles.length})</label>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive"
                                            on:click={() => {
                                                gradeFiles = [];
                                                form.files = [];
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
                        <h4 class="kt-card-title">Grade Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Grade Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Grade Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter grade title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Grade Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Grade Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter grade description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Grade Content -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="summernote-editor">
                                    Grade Content <span class="text-destructive">*</span>
                                </label>
                                <Summernote
                                    bind:this={summernoteComponent}
                                    id="summernote-editor"
                                    bind:value={form.content}
                                    placeholder="Enter grade content"
                                    height={400}
                                    minHeight={300}
                                    maxHeight={600}
                                    on:change={(event) => {
                                        form.content = event.detail.contents;
                                    }}
                                />
                                {#if errors.content}
                                    <p class="text-sm text-destructive">{errors.content}</p>
                                {/if}
                            </div>
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
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-plus text-base"></i>
                            Create Grade
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 