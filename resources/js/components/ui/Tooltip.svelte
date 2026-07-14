<script>
    /**
     * Tooltip — lightweight hover/focus label (CSS only, no deps). Wrap a trigger:
     *   <Tooltip text="Delete"><Button …/></Tooltip>
     */
    let { text = '', placement = 'top', children } = $props();

    const positions = {
        top: 'bottom-full left-1/2 -translate-x-1/2 mb-1.5',
        bottom: 'top-full left-1/2 -translate-x-1/2 mt-1.5',
        left: 'right-full top-1/2 -translate-y-1/2 me-1.5',
        right: 'left-full top-1/2 -translate-y-1/2 ms-1.5',
    };
</script>

<span class="group relative inline-flex">
    {@render children?.()}
    {#if text}
        <span
            class="pointer-events-none absolute z-50 whitespace-nowrap rounded-md bg-mono px-2 py-1 text-xs text-mono-foreground opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100 {positions[placement] ?? positions.top}"
            role="tooltip"
        >
            {text}
        </span>
    {/if}
</span>
