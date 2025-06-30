<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let menuItem;
    export let menu;
    export let languages;
    export let translations;

    // Define breadcrumbs for this menu item
    const breadcrumbs = [
        {
            title: 'Menus Management',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: menuItem?.menu?.name || 'Menu',
            url: route('admin.menu-items.index', { menu: menuItem?.menu.id }),
            active: false
        },
        {
            title: menuItem?.name || 'Menu Item Details',
            url: route('admin.menu-items.show', { menuItem: menuItem?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Menu Item Details';

    // Get linkable type display name
    function getLinkableTypeDisplay(type) {
        if (!type) return 'N/A';
        
        const typeMap = {
            'App\\Models\\Page': 'Page',
            'App\\Models\\Program': 'Program',
            'App\\Models\\Article': 'Article',
            'App\\Models\\Album': 'Album',
            'App\\Models\\Event': 'Event',
            'App\\Models\\Grade': 'Grade',
            'App\\Models\\JobPosting': 'Job'
        };
        
        return typeMap[type] || type;
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
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Menu Item Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Menu Item Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View menu item details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menu-items.index', { menu: menuItem?.menu.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Menu Items
                    </a>
                    {#if hasPermission('admin.menu-items.update')}
                        <a href={route('admin.menu-items.edit', { menuItem: menuItem?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Menu Item
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Menu Item Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Menu Item Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <!-- Menu Item Name -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Menu Item Name</h4>
                            <p class="text-sm text-secondary-foreground">{menuItem?.name}</p>
                        </div>

                        <!-- External/Internal Link -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Linkable</h4>
                            {#if menuItem.linkable_id && menuItem.linkable_type}
                                <span class="kt-badge kt-badge-success w-fit">
                                    <i class="ki-filled ki-check text-xs"></i>
                                    Yes ({menuItem.linkable_type})
                                </span>
                            {:else}
                                <span class="kt-badge kt-badge-secondary w-fit">
                                    <i class="ki-filled ki-cross text-xs"></i>
                                    No
                                </span>
                            {/if}
                        </div>

                        <!-- Url -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Url / Link</h4>
                            {#if menuItem.linkable_id && menuItem.linkable_type}
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    Link: <a href={menuItem.url} target="_blank">
                                        Open <i class="ki-filled ki-arrow-up-right"></i> 
                                    </a>
                                </span>
                            {:else}
                                <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                    Link: <a href={menuItem.url} target="_blank">
                                        Open <i class="ki-filled ki-arrow-up-right"></i> 
                                    </a>
                                </span>
                            {/if}
                        </div>

                        <!-- Created At -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Created At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {menuItem?.created_at ? new Date(menuItem.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A'}
                            </p>
                        </div>

                        <!-- Updated At -->
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-mono">Updated At</h4>
                            <p class="text-sm text-secondary-foreground">
                                {menuItem?.updated_at ? new Date(menuItem.updated_at).toLocaleDateString('en-US', {
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

            <!-- Translations Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Translations</h4>
                    <p class="text-sm text-secondary-foreground">
                        Menu item title translations
                    </p>
                </div>
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
                                        Menu Item {language.name} Title
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