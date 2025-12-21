<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let achievement;
    export let languages;
    export let translations;

    // Define breadcrumbs for this achievement
    const breadcrumbs = [
        {
            title: 'Achievements',
            url: route('admin.achievements.index'),
            active: false
        },
        {
            title: achievement?.name || 'Achievement Details',
            url: route('admin.achievements.show', { achievement: achievement?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Achievement Details';

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
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Achievement Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Achievement Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View achievement details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.achievements.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.achievements.update')}
                        <a href={route('admin.achievements.edit', { achievement: achievement?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Achievement
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Achievement Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Achievement Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        
                        
                        <!-- Achievement Details -->
                        <div class="grid gap-4 w-full">
                            <!-- Achievement Thumbnail -->
                            {#if achievement?.thumbnailUrl}
                            <div class="flex">
                                <figure class="figure">
                                    <img 
                                        src={achievement.thumbnailUrl} 
                                        alt={achievement?.name}
                                        class="rounded-lg w-32 h-32 object-cover"
                                    />
                                </figure>
                            </div>
                            {/if}

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Achievement Name</h4>
                                <p class="text-sm text-secondary-foreground">{achievement?.name || 'N/A'}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Achievement Slug</h4>
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    {achievement?.slug || 'N/A'}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Achievement Date</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {achievement?.achievement_date ? new Date(achievement.achievement_date).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) : 'N/A'}
                                </p>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Achievement Status</h4>
                                <span class="kt-badge {getStatusBadgeClass(achievement?.status)} w-fit">
                                    {getStatusText(achievement?.status)}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Achievement Category</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {achievement?.category?.name || 'N/A'}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Linkable</h4>
                                {#if achievement?.linkable}
                                    <div class="flex flex-col gap-1">
                                        <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                            {achievement.linkable_type ? achievement.linkable_type.split('\\').pop() : 'N/A'} - {achievement.linkable?.name || achievement.linkable?.slug || 'N/A'}
                                        </span>
                                        {#if achievement?.url}
                                            <a href={achievement.url} target="_blank" class="text-sm text-primary hover:underline">
                                                {achievement.url}
                                            </a>
                                        {/if}
                                    </div>
                                {:else if achievement?.url}
                                    <a href={achievement.url} target="_blank" class="text-sm text-primary hover:underline">
                                        {achievement.url}
                                    </a>
                                {:else}
                                    <p class="text-sm text-secondary-foreground">N/A</p>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {achievement?.created_at ? new Date(achievement.created_at).toLocaleDateString('en-US', {
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
                                    {achievement?.updated_at ? new Date(achievement.updated_at).toLocaleDateString('en-US', {
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
                                        Achievement {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <!-- Description Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Achievement {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
                                </div>

                                <!-- Done By Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Achievement {language.name} Done By
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('done_by', language.code)}
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