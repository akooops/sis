<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';

    export let nationality;
    export let languages;
    export let translations;

    const breadcrumbs = [
        {
            title: 'Nationalities',
            url: route('admin.nationalities.index'),
            active: false
        },
        {
            title: nationality?.name || nationality?.code || 'Nationality Details',
            url: route('admin.nationalities.show', { nationality: nationality?.id }),
            active: true
        }
    ];

    const pageTitle = 'Nationality Details';

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
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Nationality Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View nationality details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href={route('admin.nationalities.index')} class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.nationalities.update')}
                        <a href={route('admin.nationalities.edit', { nationality: nationality?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Nationality
                        </a>
                    {/if}
                </div>
            </div>

            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Nationality Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4 w-full">
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Nationality Name</h4>
                            <p class="text-sm text-secondary-foreground">
                                {nationality?.name || 'N/A'}
                            </p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Nationality Code</h4>
                            <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                {nationality?.code}
                            </span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Created At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {nationality?.created_at ? new Date(nationality.created_at).toLocaleDateString('en-US', {
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
                                {nationality?.updated_at ? new Date(nationality.updated_at).toLocaleDateString('en-US', {
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

            <div class="kt-card w-full">
                <div class="kt-card-content">
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

                    {#each languages as language, index}
                        <div class="grow flex flex-col {index === 0 ? '' : 'hidden'}" id="translation_tab_{language.code}">
                            <div class="grid gap-6 w-full py-4">
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Nationality {language.name} Title
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
</AdminLayout>
