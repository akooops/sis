<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';

    // Props
    export let programStream;
    export let languages;
    export let translations;

    // Define breadcrumbs for this stream
    const breadcrumbs = [
        {
            title: 'Programs',
            url: route('admin.programs.index'),
            active: false
        },
        {
            title: programStream?.program?.name || 'Program',
            url: route('admin.program-streams.index', { program: programStream?.program?.id }),
            active: false
        },
        {
            title: 'Streams',
            url: route('admin.program-streams.index', { program: programStream?.program?.id }),
            active: false
        },
        {
            title: programStream?.name || 'Stream Details',
            url: route('admin.program-streams.show', { programStream: programStream?.id }),
            active: true
        }
    ];

    const pageTitle = 'Stream Details';

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
            <!-- Stream Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Stream Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View stream details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.program-streams.index', { program: programStream?.program?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.program-streams.update')}
                        <a href={route('admin.program-streams.edit', { programStream: programStream?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Stream
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Stream Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Stream Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Program</h4>
                                <p class="text-sm text-secondary-foreground">{programStream?.program?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Stream Name</h4>
                                <p class="text-sm text-secondary-foreground">{programStream?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Stream Slug</h4>
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    {programStream?.slug}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {programStream?.created_at ? new Date(programStream.created_at).toLocaleDateString('en-US', {
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
                                    {programStream?.updated_at ? new Date(programStream.updated_at).toLocaleDateString('en-US', {
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
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Stream {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Stream {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Stream {language.name} Content
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
