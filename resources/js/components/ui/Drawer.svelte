<script>
    /**
     * Drawer — Metronic-style floating end drawer (the `kt-drawer kt-drawer-end
     * card` look), driven by Svelte state (bindable `open`) rather than the
     * hidden-button + KTUI reparent dance. Portals to <body>, backdrop + Escape
     * close. Used for View / Filters / pivot drawers.
     */
    import { fade, fly } from 'svelte/transition';
    import { portal } from '@/lib/portal';

    let {
        open = $bindable(false),
        title = null,
        side = 'end',
        width = 'w-[450px]',
        closeOnBackdrop = true,
        header,
        footer,
        children,
    } = $props();

    const sideClass = side === 'start' ? 'start-5' : 'end-5';
    const flyX = side === 'start' ? -480 : 480;

    function close() {
        open = false;
    }
    function onKeydown(e) {
        if (e.key === 'Escape') close();
    }

    $effect(() => {
        if (!open) return;
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => (document.body.style.overflow = prev);
    });
</script>

<svelte:window onkeydown={open ? onKeydown : undefined} />

{#if open}
    <div class="fixed inset-0 z-100" use:portal role="presentation">
        <div class="absolute inset-0 bg-black/40" transition:fade={{ duration: 150 }} onclick={closeOnBackdrop ? close : undefined}></div>

        <div
            class="absolute top-5 bottom-5 {sideClass} flex max-w-[90%] {width} flex-col rounded-xl border border-border bg-background shadow-xl"
            transition:fly={{ x: flyX, duration: 300 }}
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-3 border-b border-b-border">
                {#if header}
                    {@render header()}
                {:else}
                    <div class="flex items-center gap-2">{title}</div>
                {/if}
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" onclick={close} aria-label="Close">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <div class="kt-card-content grow flex flex-col gap-4 p-5 overflow-y-auto">
                {@render children?.()}
            </div>

            {#if footer}
                <div class="flex justify-end gap-2 border-t border-border px-5 py-3">
                    {@render footer()}
                </div>
            {/if}
        </div>
    </div>
{/if}
