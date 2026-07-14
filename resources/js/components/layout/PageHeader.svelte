<script>
    /** PageHeader — page title + optional actions row above the content. */
    let { title = null, subtitle = null, actions, children } = $props();
</script>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-col gap-0.5">
        {#if title}<h1 class="text-xl font-semibold text-mono">{title}</h1>{/if}
        {#if subtitle}<p class="text-sm text-secondary-foreground">{subtitle}</p>{/if}
        {@render children?.()}
    </div>
    {#if actions}
        <div class="flex items-center gap-2">{@render actions()}</div>
    {/if}
</div>
