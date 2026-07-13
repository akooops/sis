<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import { page } from '@inertiajs/svelte';
    import { dashboards } from './dashboards.js';

    const breadcrumbs = [
        {
            title: 'Dashboard',
            url: route('web.admin.dashboard.index'),
            active: true,
        },
    ];

    const pageTitle = 'Dashboard';

    function checkPermission(permission) {
        if (!$page.props.auth?.enable_permissions) {
            return true;
        }

        return $page.props.auth.permissions?.some((p) => p === permission) ?? false;
    }

    let activeDashboardId = null;

    $: visibleDashboards = dashboards.filter((dashboard) => checkPermission(dashboard.permission));
    $: if (activeDashboardId === null && visibleDashboards.length > 0) {
        activeDashboardId = visibleDashboards[0].id;
    }
    $: if (activeDashboardId && !visibleDashboards.some((dashboard) => dashboard.id === activeDashboardId)) {
        activeDashboardId = visibleDashboards[0]?.id ?? null;
    }
    $: activeDashboard = visibleDashboards.find((dashboard) => dashboard.id === activeDashboardId);

    function setActiveDashboard(dashboardId) {
        activeDashboardId = dashboardId;
    }
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        {#if visibleDashboards.length === 0}
            <div class="kt-card">
                <div class="kt-card-content p-8 text-center">
                    <div class="w-16 h-16 bg-accent/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-lock text-muted-foreground text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-foreground">No dashboards available</h3>
                    <p class="text-xs text-muted-foreground mt-1">
                        You do not have permission to view any dashboards.
                    </p>
                </div>
            </div>
        {:else}
            <div class="kt-card">
                <div class="kt-card-content p-2">
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        {#each visibleDashboards as dashboard}
                            <button
                                type="button"
                                class="kt-btn shrink-0 {activeDashboardId === dashboard.id ? 'kt-btn-primary' : 'kt-btn-outline'}"
                                on:click={() => setActiveDashboard(dashboard.id)}
                            >
                                <i class="fa-solid {dashboard.icon} me-2"></i>
                                {dashboard.label}
                            </button>
                        {/each}
                    </div>
                </div>
            </div>

            {#if activeDashboard}
                <svelte:component this={activeDashboard.component} />
            {/if}
        {/if}
    </div>
</MainLayout>
