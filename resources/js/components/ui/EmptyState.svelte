<script>
    /** EmptyState — centered icon + message for empty/no-results tables. */
    let { icon = 'ki-filled ki-file', title = null, body = null, action, children } = $props();
</script>

<div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
    <i class="{icon} text-4xl text-muted-foreground"></i>
    {#if title}<h3 class="text-base font-semibold text-mono">{title}</h3>{/if}
    {#if body}<p class="max-w-sm text-sm text-secondary-foreground">{body}</p>{/if}
    {@render children?.()}
    {#if action}<div class="mt-1">{@render action()}</div>{/if}
</div>
