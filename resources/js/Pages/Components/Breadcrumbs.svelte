<script>
    // Props for the breadcrumbs component
    export let breadcrumbs = [];
    export let pageTitle = 'Dashboard';
</script>

<!-- Breadcrumbs -->
<div class="pb-5">
    <!-- Container -->
    <div class="kt-container-fixed flex items-center justify-between flex-wrap gap-3">
        <div class="flex flex-col flex-wrap gap-1">
            <h1 class="font-medium text-lg text-mono">
                {pageTitle}
            </h1>
            <div class="flex items-center gap-1 text-sm font-normal">
                {#each breadcrumbs as breadcrumb, index}
                    {#if index > 0}
                        <span class="text-muted-foreground text-sm">
                            /
                        </span>
                    {/if}
                    
                    {#if breadcrumb.active || !breadcrumb.url}
                        <span class="text-mono">
                            {breadcrumb.title}
                        </span>
                    {:else}
                        <a class="text-secondary-foreground hover:text-primary" href={breadcrumb.url}>
                            {breadcrumb.title}
                        </a>
                    {/if}
                {/each}
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>
<!-- End of Breadcrumbs -->