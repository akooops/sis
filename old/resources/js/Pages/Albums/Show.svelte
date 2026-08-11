<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let album;
    export let languages;
    export let translations;

    // Define breadcrumbs for this album
    const breadcrumbs = [
        {
            title: 'Albums',
            url: route('admin.albums.index'),
            active: false
        },
        {
            title: album?.name || 'Album Details',
            url: route('admin.albums.show', { album: album?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Album Details';

    // Get status badge class
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'published':
                return 'kt-badge-success';
            case 'draft':
                return 'kt-badge-info';
            case 'hidden':
                return 'kt-badge-primary';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get status text
    function getStatusText(status) {
        switch (status) {
            case 'published':
                return 'Published';
            case 'draft':
                return 'Draft';
            case 'hidden':
                return 'Hidden';
            default:
                return status;
        }
    }

    // Get translation for a field and language
    function getTranslation(field, languageCode) {
        if (translations && translations[field] && translations[field][languageCode]) {
            return translations[field][languageCode];
        }
        return `${field}.${languageCode}`;
    }

    // Format file size
    function formatFileSize(bytes) {
        if (!bytes) return '';
        
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Album Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Album Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View album details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.albums.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.albums.update')}
                        <a href={route('admin.albums.edit', { album: album?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Album
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Album Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Album Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Album Details -->
                        <div class="grid gap-4 w-full">
                            <!-- Album Thumbnail -->
                            <div class="flex">
                                <figure class="figure">
                                    <img 
                                        src={album?.thumbnailUrl} 
                                        alt={album?.name}
                                        class="rounded-lg w-32 h-32 object-cover"
                                    />
                                </figure>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Album Name</h4>
                                <p class="text-sm text-secondary-foreground">{album?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Album Slug</h4>
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    {album?.slug}
                                </span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Album Status</h4>
                                <span class="kt-badge {getStatusBadgeClass(album?.status)} w-fit">
                                    {getStatusText(album?.status)}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {album?.created_at ? new Date(album.created_at).toLocaleDateString('en-US', {
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
                                    {album?.updated_at ? new Date(album.updated_at).toLocaleDateString('en-US', {
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

            
            <!-- Album Files Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Album Files</h4>
                    <p class="text-sm text-secondary-foreground">
                        Images and videos in this album ({album?.files?.length || 0} files)
                    </p>
                </div>
                <div class="kt-card-content">
                    {#if album?.files && album.files.length > 0}
                        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                            {#each album.files as file}
                                <div class="relative group">
                                    <div class="relative overflow-hidden rounded-lg border bg-muted w-40 h-40">
                                        {#if file.type && file.type.startsWith('image/')}
                                            <img 
                                                src={file.url} 
                                                alt={file.original_name || 'Image'}
                                                class="w-full h-full object-cover"
                                                style="object-fit: cover;"
                                            />
                                        {:else if file.type && file.type.startsWith('video/')}
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
                                            <!-- Fallback for unknown file types -->
                                            <div class="w-full h-full flex items-center justify-center bg-muted">
                                                <i class="ki-filled ki-file text-2xl text-secondary-foreground"></i>
                                            </div>
                                        {/if}
                                        
                                        <!-- File type indicator -->
                                        <div class="absolute bottom-2 left-2">
                                            {#if file.type && file.type.startsWith('image/')}
                                                <span class="kt-badge kt-badge-xs kt-badge-primary">IMG</span>
                                            {:else if file.type && file.type.startsWith('video/')}
                                                <span class="kt-badge kt-badge-xs kt-badge-success">VID</span>
                                            {:else}
                                                <span class="kt-badge kt-badge-xs kt-badge-secondary">FILE</span>
                                            {/if}
                                        </div>
                                    </div>
                                    
                                    <!-- File name -->
                                    <p class="text-xs text-secondary-foreground mt-2 truncate" title={file.original_name || 'File'}>
                                        {file.original_name || 'File'}
                                    </p>
                                    
                                    <!-- File size -->
                                    <p class="text-xs text-secondary-foreground">
                                        {file.size ? formatFileSize(file.size) : ''}
                                    </p>
                                </div>
                            {/each}
                        </div>
                    {:else}
                        <div class="text-center py-8">
                            <i class="ki-filled ki-folder text-4xl text-secondary-foreground mb-4"></i>
                            <p class="text-sm text-secondary-foreground">No files uploaded to this album yet.</p>
                        </div>
                    {/if}
                </div>
            </div>
            
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
                                        Album {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <!-- Description Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Album {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
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