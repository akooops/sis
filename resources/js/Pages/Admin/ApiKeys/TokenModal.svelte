<script>
    /** Shows a freshly-issued API token once, with copy-to-clipboard. */
    import Modal from '@/components/ui/Modal.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), token = '' } = $props();

    async function copy() {
        try {
            await navigator.clipboard.writeText(token);
            toast.success('Copied');
        } catch {
            toast.error('Something went wrong. Please try again.');
        }
    }
</script>

<Modal bind:open size="md" title="Copy your API token" closeOnBackdrop={false}>
    <div class="flex flex-col gap-3">
        <Alert variant="warning">This token is shown only once. Store it securely now.</Alert>
        <code class="block break-all rounded-lg border border-border bg-muted p-3 font-mono text-sm">{token}</code>
        <Button variant="primary" onclick={copy}><i class="ki-filled ki-copy"></i>Copy</Button>
    </div>
</Modal>
