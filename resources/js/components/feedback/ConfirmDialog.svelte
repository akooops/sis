<script>
    /** ConfirmDialog — renders the pending confirm() request (mount once in a layout). */
    import Modal from '@/components/ui/Modal.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { confirmState, resolveConfirm } from '@/lib/confirm';

    let open = $state(false);

    // Open whenever a confirm() request is pending.
    $effect(() => {
        open = $confirmState !== null;
    });

    function decide(value) {
        resolveConfirm(value);
    }

    // Escape / backdrop / × dismissal counts as cancel.
    function onDismiss() {
        if ($confirmState) resolveConfirm(false);
    }
</script>

<Modal bind:open size="sm" title={$confirmState?.title ?? 'Delete confirmation'} onclose={onDismiss}>
    <p class="text-sm text-secondary-foreground">
        {$confirmState?.body ?? 'Are you sure you want to delete this record? This action cannot be undone.'}
    </p>

    {#snippet footer()}
        <Button variant="secondary" onclick={() => decide(false)}>
            {$confirmState?.cancelLabel ?? 'Cancel'}
        </Button>
        <Button variant={$confirmState?.variant ?? 'primary'} onclick={() => decide(true)}>
            {$confirmState?.confirmLabel ?? 'Confirm'}
        </Button>
    {/snippet}
</Modal>
