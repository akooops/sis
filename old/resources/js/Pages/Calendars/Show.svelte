<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let calendar;
    export let languages;
    export let translations;

    // Define breadcrumbs for this calendar
    const breadcrumbs = [
        {
            title: 'Calendars',
            url: route('admin.calendars.index'),
            active: false
        },
        {
            title: calendar?.name || 'Calendar Details',
            url: route('admin.calendars.show', { calendar: calendar?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Calendar Details';

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
            <!-- Calendar Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Calendar Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View calendar details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.calendars.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.calendars.update')}
                        <a href={route('admin.calendars.edit', { calendar: calendar?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Calendar
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Calendar Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Calendar Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Calendar Details -->
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Calendar Name</h4>
                                <p class="text-sm text-secondary-foreground">{calendar?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Calendar Url</h4>
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    <a href={calendar?.calendarUrl} target="_blank">
                                        Open <i class="ki-filled ki-arrow-up-right"></i> 
                                    </a>
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Active</h4>
                                <span class="kt-badge kt-badge-outline w-fit kt-badge-{calendar?.is_active ? 'success' : 'danger'}">
                                    {calendar?.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Start Date</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {calendar?.starts_at ? new Date(calendar.starts_at).toLocaleString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">End Date</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {calendar?.ends_at ? new Date(calendar.ends_at).toLocaleString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {calendar?.created_at ? new Date(calendar.created_at).toLocaleDateString('en-US', {
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
                                    {calendar?.updated_at ? new Date(calendar.updated_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>
                        </div>

                        <!-- Calendar Thumbnail -->
                        {#if calendar?.thumbnailUrl}
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Thumbnail</h4>
                                <div class="relative inline-block">
                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                        <img 
                                            src={calendar.thumbnailUrl} 
                                            alt="Calendar thumbnail"
                                            class="w-48 h-48 object-cover rounded-lg" 
                                        />
                                    </div>
                                </div>
                            </div>
                        {/if}
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
                                        Calendar {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
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