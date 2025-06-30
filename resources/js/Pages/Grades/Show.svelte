<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let grade;
    export let languages;
    export let translations;

    // Define breadcrumbs for this grade
    const breadcrumbs = [
        {
            title: 'Grades',
            url: route('admin.grades.index'),
            active: false
        },
        {
            title: grade?.name || 'Grade Details',
            url: route('admin.grades.show', { grade: grade?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Grade Details';

    // Get translation for a field and language
    function getTranslation(field, languageCode) {
        if (translations && translations[field] && translations[field][languageCode]) {
            return translations[field][languageCode];
        }
        return `${field}.${languageCode}`;
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

    // Process grade files for display
    let gradeFiles = [];
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
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Grade Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Grade Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View grade details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.grades.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.grades.update')}
                        <a href={route('admin.grades.edit', { grade: grade?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Grade
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Grade Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Grade Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        
                        
                        <!-- Grade Details -->
                        <div class="grid gap-4 w-full">
                            <!-- Grade Thumbnail -->
                            <div class="flex">
                                <figure class="figure">
                                    <img 
                                        src={grade?.thumbnailUrl} 
                                        alt={grade?.name}
                                        class="rounded-lg w-32 h-32 object-cover"
                                    />
                                </figure>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Grade Name</h4>
                                <p class="text-sm text-secondary-foreground">{grade?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Grade Slug</h4>
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    {grade?.slug}
                                </span>
                            </div>

                            <!-- Program Information -->
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Grade Program</h4>
                                {#if grade?.program}
                                    <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                        <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                        {grade.program.name}
                                    </span>
                                {:else}
                                    <p class="text-sm text-secondary-foreground">No program assigned</p>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {grade?.created_at ? new Date(grade.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Updated At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {grade?.updated_at ? new Date(grade.updated_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grade Files Card -->
            {#if gradeFiles.length > 0}
                <div class="kt-card w-full">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Grade Files</h4>
                        <p class="text-sm text-secondary-foreground">
                            Documents, images, and videos associated with this grade
                        </p>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-mono">Grade Files ({gradeFiles.length})</label>
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
                    </div>
                </div>
            {/if}

            <!-- Translations Card -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
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
                            <div class="grid gap-6 w-full py-4">
                                <!-- Title Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Grade {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <!-- Description Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Grade {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
                                </div>

                                <!-- Content Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Grade {language.name} Content
                                    </h4>
                                    <div class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {@html getTranslation('content', language.code)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout> 