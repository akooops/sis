<script>
    /** Toasts — renders the toast stack (mount once in a layout). */
    import { fly } from 'svelte/transition';
    import { portal } from '@/lib/portal';
    import { toasts, dismissToast } from '@/lib/toast';

    const variants = {
        info: 'kt-alert-info',
        success: 'kt-alert-success',
        warning: 'kt-alert-warning',
        destructive: 'kt-alert-destructive',
    };
</script>

<div class="fixed bottom-5 end-5 z-100 flex flex-col gap-2" use:portal>
    {#each $toasts as t (t.id)}
        <div
            class="kt-alert {variants[t.variant] ?? variants.info} min-w-[260px] max-w-sm shadow-lg"
            role="alert"
            transition:fly={{ y: 20, duration: 200 }}
        >
            <div class="kt-alert-content grow">{t.message}</div>
            <button class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost" onclick={() => dismissToast(t.id)} aria-label="Dismiss">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
    {/each}
</div>
