<script>
    /**
     * MediaUploader — drag/drop or click to upload a single file to the upload
     * endpoint. Calls `onuploaded(mediaData)` with the returned reference.
     */
    import Spinner from '@/components/ui/Spinner.svelte';
    import { uploadFile, mediaConfig, acceptFor, acceptForTypes, typeForFile, MEDIA_TYPES } from '@/lib/upload';

    // `accept` restricts to a subset of types (e.g. ['images']); default = all.
    let { accept = null, onuploaded } = $props();

    let uploading = $state(false);
    let error = $state(null);
    let dragging = $state(false);

    const cfg = mediaConfig();
    const allowedTypes = $derived(accept?.length ? accept : MEDIA_TYPES);
    const singleType = $derived(allowedTypes.length === 1 ? allowedTypes[0] : null);
    const inputAccept = $derived(singleType ? acceptFor(singleType) : acceptForTypes(allowedTypes));

    async function handle(files) {
        const file = files?.[0];
        if (!file) return;
        // A single-type uploader forces that type; a multi-type one infers it.
        const type = singleType ?? typeForFile(file.name, allowedTypes);
        if (!type) {
            error = 'Unsupported file type.';
            return;
        }
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
    <input type="file" class="hidden" accept={inputAccept} onchange={(e) => handle(e.currentTarget.files)} disabled={uploading} />
    {#if uploading}
        <Spinner />
        <span class="text-sm text-secondary-foreground">Scanning…</span>
    {:else}
        <i class="ki-filled ki-cloud-add text-3xl text-muted-foreground"></i>
        <span class="text-sm text-secondary-foreground">Drag &amp; drop a file here, or click to browse</span>
        <span class="text-xs text-muted-foreground">Maximum size: {cfg.max_file_size_human}</span>
    {/if}
    {#if error}<span class="text-xs text-destructive">{error}</span>{/if}
</label>
