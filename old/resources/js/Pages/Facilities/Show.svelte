<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let facility;
    export let languages;
    export let translations;

    // Define breadcrumbs for this facility
    const breadcrumbs = [
        {
            title: 'Facilities',
            url: route('admin.facilities.index'),
            active: false
        },
        {
            title: facility?.name || 'Facility Details',
            url: route('admin.facilities.show', { facility: facility?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Facility Details';

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
            <!-- Facility Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Facility Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View facility details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.facilities.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.facilities.update')}
                        <a href={route('admin.facilities.edit', { facility: facility?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Facility
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Facility Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Facility Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        
                        <!-- Facility Details -->
                        <div class="grid gap-4 w-full">
                            <!-- Facility Thumbnail -->
                            <div class="flex">
                                <figure class="figure">
                                    <img 
                                        src={facility?.thumbnailUrl} 
                                        alt={facility?.name}
                                        class="rounded-lg w-32 h-32 object-cover"
                                    />
                                </figure>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Facility Name</h4>
                                <p class="text-sm text-secondary-foreground">{facility?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Status</h4>
                                <span class="kt-badge kt-badge-outline {facility?.status === 'published' ? 'kt-badge-success' : 'kt-badge-warning'} w-fit">
                                    {facility?.status}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Slug</h4>
                                <p class="text-sm text-secondary-foreground">/facilities/{facility?.slug}</p>
                            </div>

                            {#if facility?.domain}
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">Subdomain</h4>
                                    <p class="text-sm text-secondary-foreground">{facility?.domain}</p>
                                </div>
                            {/if}

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Contact</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {facility?.email || '—'} · {facility?.phone || '—'} · WhatsApp: {facility?.whatsapp || '—'}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                {#if hasPermission('admin.facility-time-slots.index')}
                                    <a href={route('admin.facility-time-slots.index', { facility: facility?.id })} class="kt-btn kt-btn-sm kt-btn-outline">
                                        <i class="ki-filled ki-calendar-8 text-base"></i>
                                        Time Slots
                                    </a>
                                {/if}
                                {#if hasPermission('admin.facility-reservations.index')}
                                    <a href={route('admin.facility-reservations.index', { facility: facility?.id })} class="kt-btn kt-btn-sm kt-btn-outline">
                                        <i class="ki-filled ki-calendar-tick text-base"></i>
                                        Reservations
                                    </a>
                                {/if}
                                <a href={`/facilities/${facility?.slug}`} target="_blank" class="kt-btn kt-btn-sm kt-btn-outline">
                                    <i class="ki-filled ki-exit-right-corner text-base"></i>
                                    Visit Site
                                </a>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {facility?.created_at ? new Date(facility.created_at).toLocaleDateString('en-US', {
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
                                    {facility?.updated_at ? new Date(facility.updated_at).toLocaleDateString('en-US', {
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
                                        Facility {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <!-- Description Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Facility {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
                                </div>

                                <!-- Content Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Facility {language.name} Content
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