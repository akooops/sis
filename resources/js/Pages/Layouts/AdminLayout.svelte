<script>
  import Sidebar from '../Components/Sidebar.svelte';
  import Topbar from '../Components/Topbar.svelte';
  import Breadcrumbs from '../Components/Breadcrumbs.svelte';
  
  // Props for the layout
  export let breadcrumbs = [];
  export let pageTitle = 'Dashboard';
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
        <div class="flex flex-col grow lg:rounded-l-xl bg-background border border-input lg:ms-(--sidebar-width) h-screen" style="min-height: 100vh; width: 100%;">
            <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5 overflow-y-auto" id="scrollable_content">
                <main class="grow" role="content">
                    <Breadcrumbs {breadcrumbs} {pageTitle} />

                    <slot />
                </main>
            </div>
        </div>
        <!-- End of Main -->
    </div>
</div>