<script>
    /** Shows a freshly-issued API token once, with copy-to-clipboard. */
    import Modal from '@/components/ui/Modal.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import { toast } from '@/lib/toast';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), token = '' } = $props();

    async function copy() {
        try {
            await navigator.clipboard.writeText(token);
            toast.success($t('api_keys.token.copied'));
        } catch {
            toast.error($t('common.feedback.error'));
        }
    }
</script>

<Modal bind:open size="md" title={$t('api_keys.token.title')} closeOnBackdrop={false}>
    <div class="flex flex-col gap-3">
        <Alert variant="warning">{$t('api_keys.token.warning')}</Alert>
        <code class="block break-all rounded-lg border border-border bg-muted p-3 font-mono text-sm">{token}</code>
        <Button variant="primary" onclick={copy}><i class="ki-filled ki-copy"></i>{$t('api_keys.token.copy')}</Button>
    </div>
</Modal>
