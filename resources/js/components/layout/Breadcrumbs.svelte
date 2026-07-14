<script>
    /** Breadcrumbs — Metronic header breadcrumbs. items: [{ label, href? }]. */
    import { inertia } from '@inertiajs/svelte';
    let { items = [] } = $props();
</script>

<div class="flex items-center gap-1.25 text-xs lg:text-sm font-medium">
    {#each items as item, i}
        {#if i > 0}
            <i class="ki-filled ki-right text-muted-foreground text-[10px] rtl:rotate-180"></i>
        {/if}
        {#if item.href && i < items.length - 1}
            <a class="text-mono hover:text-primary" href={item.href} use:inertia>{item.label}</a>
        {:else}
            <span class="text-mono font-medium">{item.label}</span>
        {/if}
    {/each}
</div>
