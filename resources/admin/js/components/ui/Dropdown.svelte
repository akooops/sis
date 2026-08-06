<script>
    /**
     * Dropdown — click-toggle menu, Svelte-driven (no KTUI re-init needed for
     * client-rendered rows). The menu is portaled to <body> and positioned from
     * the trigger, so it's never clipped by a table's overflow. Metronic styling.
     *
     *   <Dropdown>
     *       {#snippet trigger()}<Button size="icon">…</Button>{/snippet}
     *       <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss …>Edit</button></div>
     *   </Dropdown>
     */
    import { portal } from '@/lib/portal';

    let { placement = 'bottom-end', children, trigger } = $props();

    let open = $state(false);
    let anchor;
    let menu;
    let pos = $state({ top: 0, left: 0 });

    function place() {
        if (!anchor) return;
        const r = anchor.getBoundingClientRect();
        const width = 200;
        const left = placement.endsWith('start') ? r.left : r.right - width;
        pos = { top: r.bottom + 4, left: Math.max(8, left) };
    }

    function toggle() {
        open = !open;
        if (open) place();
    }

    function onWindow(event) {
        if (!open) return;
        if (anchor?.contains(event.target) || menu?.contains(event.target)) return;
        open = false;
    }
    function onKeydown(event) {
        if (event.key === 'Escape') open = false;
    }
    function onMenuClick(event) {
        if (event.target.closest('[data-dropdown-dismiss]')) open = false;
    }
</script>

<svelte:window
    onclick={onWindow}
    onkeydown={open ? onKeydown : undefined}
    onscroll={open ? place : undefined}
    onresize={open ? place : undefined}
/>

<div class="inline-flex" bind:this={anchor} onclick={toggle} role="button" tabindex="0">
    {@render trigger?.()}
</div>

{#if open}
    <div
        class="kt-menu kt-menu-default flex flex-col fixed z-100 min-w-[200px] rounded-lg border border-border bg-popover py-1.5 shadow-lg"
        style="top:{pos.top}px; left:{pos.left}px;"
        use:portal
        bind:this={menu}
        onclick={onMenuClick}
        role="menu"
    >
        {@render children?.()}
    </div>
{/if}
