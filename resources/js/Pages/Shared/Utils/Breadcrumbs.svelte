<script>
    import { inertia } from '@inertiajs/svelte';
    
    // Props for the breadcrumbs component
    export let breadcrumbs = [];
    export let pageTitle = 'Dashboard';
</script>

<!-- Breadcrumbs -->
<div class="flex [.kt-header_&]:below-lg:hidden items-center gap-1.25 text-xs lg:text-sm font-medium mb-2.5 lg:mb-0 [--kt-reparent-target:#contentContainer] lg:[--kt-reparent-target:#headerContainer] [--kt-reparent-mode:prepend] lg:[--kt-reparent-mode:prepend]" data-kt-reparent="true">
    {#each breadcrumbs as breadcrumb, index}
        {#if index > 0}
            <i class="ki-filled ki-right text-muted-foreground text-[10px]"></i>
        {/if}
        
        {#if breadcrumb.active || !breadcrumb.url}
            <span class="text-mono font-medium">
                {breadcrumb.title}
            </span>
        {:else}
            <a class="text-mono hover:text-primary" href={breadcrumb.url} use:inertia>
                {breadcrumb.title}
            </a>
        {/if}
    {/each}
</div>
<!-- End of Breadcrumbs -->