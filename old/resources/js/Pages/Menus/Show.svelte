<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';

    // Props
    export let menu;

    // Define breadcrumbs for this menu
    const breadcrumbs = [
        {
            title: 'Menus',
            url: route('admin.menus.index'),
            active: false
        },
        {
            title: menu?.name || 'Menu Details',
            url: route('admin.menus.show', { menu: menu?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Menu Details';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Menu Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Menu Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View menu details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.menus.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.menus.update')}
                        <a href={route('admin.menus.edit', { menu: menu?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Menu
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Menu Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Menu Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Menu Details -->
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Menu Name</h4>
                                <p class="text-sm text-secondary-foreground">{menu?.name}</p>
                            </div>
                       
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {menu?.created_at ? new Date(menu.created_at).toLocaleDateString('en-US', {
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
                                    {menu?.updated_at ? new Date(menu.updated_at).toLocaleDateString('en-US', {
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