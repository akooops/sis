<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let language;

    // Define breadcrumbs for this language
    const breadcrumbs = [
        {
            title: 'Languages',
            url: route('admin.languages.index'),
            active: false
        },
        {
            title: language?.name || 'Language Details',
            url: route('admin.languages.show', { language: language?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Language Details';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Language Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Language Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View language details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.languages.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.languages.update')}
                        <a href={route('admin.languages.edit', { language: language?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Language
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Language Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Language Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Language Details -->
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Language Name</h4>
                                <p class="text-sm text-secondary-foreground">{language?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Language Code</h4>
                                <p class="text-sm text-secondary-foreground">{language?.code}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Default Language</h4>
                                {#if language.is_default}
                                    <span class="kt-badge kt-badge-outline kt-badge-success w-fit">
                                        Default
                                    </span>
                                {:else}
                                    <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                        Not Default
                                    </span>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Right to Left</h4>
                                {#if language.is_rtl}
                                    <span class="kt-badge kt-badge-outline kt-badge-success w-fit">
                                        Yes
                                    </span>
                                {:else}
                                    <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                        No
                                    </span>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {language?.created_at ? new Date(language.created_at).toLocaleDateString('en-US', {
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
                                    {language?.updated_at ? new Date(language.updated_at).toLocaleDateString('en-US', {
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
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout> 