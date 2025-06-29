<script>
  import { page } from '@inertiajs/svelte';
  import Sidebar from '../Components/Sidebar.svelte';
  import Topbar from '../Components/Topbar.svelte';
  import Breadcrumbs from '../Components/Breadcrumbs.svelte';
  
  // Props for the layout
  export let breadcrumbs = [];
  export let pageTitle = 'Dashboard';

  // Global utility functions for admin components
  function hasPermission(permission) {
    if (!$page.props.auth.permissions) return false;
    return $page.props.auth.permissions.some(p => p === permission);
  }

  function isActiveRoute(routePattern) {
    return $page.url.startsWith(route(routePattern));
  }

  // Make functions globally available for child components
  window.hasPermission = hasPermission;
  window.isActiveRoute = isActiveRoute;
</script>

<div class="flex grow">
    <!-- Header -->
    <Topbar />
    <!-- End of Header -->

    <!-- Wrapper -->
    <div class="flex flex-col lg:flex-row grow pt-(--header-height) lg:pt-0">
        <!-- Sidebar -->
        <Sidebar />
        <!-- End of Sidebar -->

        <!-- Main -->
        <div class="flex flex-col grow lg:rounded-l-xl bg-background border border-input lg:ms-(--sidebar-width)" style="min-height: 100vh;">
            <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
                <div class="grow" role="content">
                    <Breadcrumbs {breadcrumbs} {pageTitle} />

                    <slot />
                </div>
            </div>
        </div>
        <!-- End of Main -->
    </div>
</div>