<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let partner;

    // Define breadcrumbs for this partner
    const breadcrumbs = [
        {
            title: 'Partners',
            url: route('admin.partners.index'),
            active: false
        },
        {
            title: partner?.name || 'Partner Details',
            url: route('admin.partners.show', { partner: partner?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Partner Details';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Partner Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Partner Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View partner details
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.partners.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission('admin.partners.update')}
                        <a href={route('admin.partners.edit', { partner: partner?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Partner
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Partner Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Partner Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Partner Details -->
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Partner Name</h4>
                                <p class="text-sm text-secondary-foreground">{partner?.name}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Partner URL</h4>
                                {#if partner?.url}
                                    <span class="kt-badge kt-badge-outline kt-badge-primary w-fit">
                                        <a href={partner.url} target="_blank">
                                            {partner.url} <i class="ki-filled ki-arrow-up-right"></i> 
                                        </a>
                                    </span>
                                {:else}
                                    <p class="text-sm text-secondary-foreground">-</p>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Created At</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {partner?.created_at ? new Date(partner.created_at).toLocaleDateString('en-US', {
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
                                    {partner?.updated_at ? new Date(partner.updated_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>
                        </div>

                        <!-- Partner Logo -->
                        {#if partner?.logoUrl}
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Partner Logo</h4>
                                <div class="relative inline-block">
                                    <div class="p-2 border-2 border-primary/20 bg-primary/5 rounded-lg">
                                        <img 
                                            src={partner.logoUrl} 
                                            alt={partner.name}
                                            class="w-48 h-48 object-cover rounded-lg" 
                                        />
                                    </div>
                                </div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout>

