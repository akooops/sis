<script>
    /** ConfirmDialog — renders the pending confirm() request (mount once in a layout). */
    import Modal from '@/components/ui/Modal.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { confirmState, resolveConfirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

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

<Modal bind:open size="sm" title={$confirmState?.title ?? $t('common.confirm.delete_title')} onclose={onDismiss}>
    <p class="text-sm text-secondary-foreground">
        {$confirmState?.body ?? $t('common.confirm.delete_body')}
    </p>

    {#snippet footer()}
        <Button variant="secondary" onclick={() => decide(false)}>
            {$confirmState?.cancelLabel ?? $t('common.actions.cancel')}
        </Button>
        <Button variant={$confirmState?.variant ?? 'primary'} onclick={() => decide(true)}>
            {$confirmState?.confirmLabel ?? $t('common.actions.confirm')}
        </Button>
    {/snippet}
</Modal>
