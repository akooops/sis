<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let brand;
    export let languages;
    export let translations;

    // Define breadcrumbs for this brand
    const breadcrumbs = [
        {
            title: 'Brands',
            url: route('admin.brands.index'),
            active: false
        },
        {
            title: brand?.name || 'Brand Details',
            url: route('admin.brands.show', { brand: brand?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Brand Details';

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
            <!-- Brand Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Brand Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View brand details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.brands.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.brands.update')}
                        <a href={route('admin.brands.edit', { brand: brand?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Brand
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Brand Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Brand Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        
                        <!-- Brand Details -->
                        <div class="grid gap-4 w-full">
                            <!-- Brand Thumbnail -->
                            <div class="flex">
                                <figure class="figure">
                                    <img 
                                        src={brand?.thumbnailUrl} 
                                        alt={brand?.name}
                                        class="rounded-lg w-32 h-32 object-cover"
                                    />
                                </figure>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Brand Name</h4>
                                <p class="text-sm text-secondary-foreground">{brand?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Status</h4>
                                <span class="kt-badge kt-badge-outline {brand?.status === 'published' ? 'kt-badge-success' : 'kt-badge-warning'} w-fit">
                                    {brand?.status}
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Slug</h4>
                                <p class="text-sm text-secondary-foreground">/identity/{brand?.slug}</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href={`/identity/${brand?.slug}`} target="_blank" class="kt-btn kt-btn-sm kt-btn-outline">
                                    <i class="ki-filled ki-exit-right-corner text-base"></i>
                                    Visit Site
                                </a>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {brand?.created_at ? new Date(brand.created_at).toLocaleDateString('en-US', {
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
                                    {brand?.updated_at ? new Date(brand.updated_at).toLocaleDateString('en-US', {
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
                                        Brand {language.name} Title
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('title', language.code)}
                                    </p>
                                </div>

                                <!-- Description Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Brand {language.name} Description
                                    </h4>
                                    <p class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg">
                                        {getTranslation('description', language.code)}
                                    </p>
                                </div>

                                <!-- Content Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Brand {language.name} Content
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