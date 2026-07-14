<script>
    /**
     * MediaUploader — drag/drop or click to upload a single file to the upload
     * endpoint. Calls `onuploaded(mediaData)` with the returned reference.
     */
    import Spinner from '@/components/ui/Spinner.svelte';
    import { uploadFile, mediaConfig, acceptFor } from '@/lib/upload';
    import { t } from '@/lib/i18n';

    let { type = 'images', onuploaded } = $props();

    let uploading = $state(false);
    let error = $state(null);
    let dragging = $state(false);

    const cfg = mediaConfig();

    async function handle(files) {
        const file = files?.[0];
        if (!file) return;
        uploading = true;
        error = null;
        try {
            const media = await uploadFile(file, type);
            onuploaded?.(media);
        } catch (e) {
            error = e?.message ?? 'Upload failed';
        } finally {
            uploading = false;
        }
    }
</script>

<label
    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 text-center transition-colors {dragging
        ? 'border-primary bg-primary/5'
        : 'border-border hover:border-primary/50'}"
    ondragover={(e) => {
        e.preventDefault();
        dragging = true;
    }}
    ondragleave={() => (dragging = false)}
    ondrop={(e) => {
        e.preventDefault();
        dragging = false;
        handle(e.dataTransfer.files);
    }}
>
    <input type="file" class="hidden" accept={acceptFor(type)} onchange={(e) => handle(e.currentTarget.files)} disabled={uploading} />
    {#if uploading}
        <Spinner />
        <span class="text-sm text-secondary-foreground">{$t('common.media.scanning')}</span>
    {:else}
        <i class="ki-filled ki-cloud-add text-3xl text-muted-foreground"></i>
        <span class="text-sm text-secondary-foreground">{$t('common.media.drop_here')}</span>
        <span class="text-xs text-muted-foreground">{$t('common.media.max_size', { size: cfg.max_file_size_human })}</span>
    {/if}
    {#if error}<span class="text-xs text-destructive">{error}</span>{/if}
</label>
