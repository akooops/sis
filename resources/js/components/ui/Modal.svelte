<script>
    /**
     * Modal — native Svelte 5 dialog (no KTUI JS). Bindable `open`, closes on
     * backdrop click + Escape, portals to <body>, locks scroll while open.
     *
     *   <Modal bind:open title="Edit">
     *       …body…
     *       {#snippet footer()}<Button>Save</Button>{/snippet}
     *   </Modal>
     */
    import { fade, scale } from 'svelte/transition';
    import { portal } from '@/lib/portal';

    let {
        open = $bindable(false),
        title = null,
        size = 'md',
        closeOnBackdrop = true,
        onclose,
        header,
        footer,
        children,
    } = $props();

    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
    };

    // Called only on user-initiated dismissal (Escape / backdrop / close button),
    // not when a parent sets open=false programmatically.
    function close() {
        open = false;
        onclose?.();
    }

    function onKeydown(event) {
        if (event.key === 'Escape') close();
    }

    // Lock body scroll while any modal is open.
    $effect(() => {
        if (!open) return;
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = prev;
        };
    });
</script>

<svelte:window onkeydown={open ? onKeydown : undefined} />

{#if open}
    <div class="fixed inset-0 z-100 flex items-center justify-center p-4" use:portal role="presentation">
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-black/40"
            transition:fade={{ duration: 150 }}
            onclick={closeOnBackdrop ? close : undefined}
        ></div>

        <!-- Panel -->
        <div
            class="kt-card relative z-10 w-full {sizes[size] ?? sizes.md} max-h-[90vh] overflow-hidden"
            transition:scale={{ duration: 150, start: 0.96 }}
            role="dialog"
            aria-modal="true"
        >
            {#if header || title}
                <div class="kt-card-header">
                    {#if header}
                        {@render header()}
                    {:else}
                        <h3 class="kt-card-title">{title}</h3>
                    {/if}
                    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={close} aria-label="Close">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
            {/if}

            <div class="kt-card-content overflow-y-auto">
                {@render children?.()}
            </div>

            {#if footer}
                <div class="kt-card-footer flex justify-end gap-2">
                    {@render footer()}
                </div>
            {/if}
        </div>
    </div>
{/if}
