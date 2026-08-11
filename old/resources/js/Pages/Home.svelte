<script>
    import AdminLayout from './Layouts/AdminLayout.svelte';
    
    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Dashboard',
            url: '/dashboard',
            active: false
        },
        {
            title: 'Index',
            url: '/pages/index',
            active: true
        }
    ];
    
    const pageTitle = 'Dashboard';
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">

        </div>
    </div>
    <!-- End of Container -->
</AdminLayout>