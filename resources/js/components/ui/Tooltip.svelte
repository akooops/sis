<script>
    /**
     * Tooltip — hover/focus label for icon-only controls. Wrap a trigger:
     *   <Tooltip text="Delete"><Button …/></Tooltip>
     *
     * The pill portals to <body> and positions off the trigger, so a table's
     * overflow container can't clip it — the same reason Dropdown and Select
     * portal their menus. Hidden on mousedown so it never lingers over
     * whatever the click opens.
     */
    import { portal } from '@/lib/portal';

    let { text = '', placement = 'top', children } = $props();

    let wrapper;
    let show = $state(false);
    let pos = $state({ top: 0, left: 0 });

    const transforms = {
        top: '-translate-x-1/2 -translate-y-full',
        bottom: '-translate-x-1/2',
        left: '-translate-x-full -translate-y-1/2',
        right: '-translate-y-1/2',
    };

    function enter() {
        if (!wrapper) return;
        const r = wrapper.getBoundingClientRect();
        pos = {
            top: { top: r.top - 6, left: r.left + r.width / 2 },
            bottom: { top: r.bottom + 6, left: r.left + r.width / 2 },
            left: { top: r.top + r.height / 2, left: r.left - 6 },
            right: { top: r.top + r.height / 2, left: r.right + 6 },
        }[placement] ?? { top: r.top - 6, left: r.left + r.width / 2 };
        show = true;
    }

    function leave() {
        show = false;
    }
</script>

<span class="inline-flex" bind:this={wrapper} role="presentation" onmouseenter={enter} onmouseleave={leave} onmousedown={leave} onfocusin={enter} onfocusout={leave}>
    {@render children?.()}
</span>

{#if show && text}
    <span
        use:portal
        class="pointer-events-none fixed z-120 whitespace-nowrap rounded-md bg-mono px-2 py-1 text-xs text-mono-foreground shadow-md {transforms[placement] ?? transforms.top}"
        style="top:{pos.top}px; left:{pos.left}px;"
        role="tooltip"
    >
        {text}
    </span>
{/if}
