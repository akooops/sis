<script>
    /**
     * ServerExportButton — download a file the SERVER builds.
     *
     * The sibling ExportButton writes a CSV out of the rows already on screen,
     * which is the whole table only when the table is one page long. This one
     * hands the current query to an export endpoint and streams back everything
     * that matches it.
     *
     *   <ServerExportButton
     *       routeName="api.v1.admin.form-submissions.export"
     *       routeParams={formId}
     *       params={{ filter, sort }}
     *       fallbackFilename="submissions.csv" />
     *
     * Deliberately a fetch + objectURL rather than an `<a href>` or a
     * `window.location`: a top-level navigation to an API route drops the
     * headers Sanctum's stateful guard reads, and a rejection then paints raw
     * JSON over the admin instead of raising a toast. Page/per_page are the
     * caller's to leave out — an export is never one page.
     */
    import Button from '@/components/ui/Button.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let {
        routeName,
        routeParams = undefined,
        params = {},
        fallbackFilename = 'export.csv',
        label = 'Export',
        title = null,
        variant = 'outline',
        size = 'sm',
        disabled = false,
    } = $props();

    let busy = $state(false);

    async function download() {
        if (busy) return;
        busy = true;

        let objectUrl = null;

        try {
            const { blob, filename } = await api.blob(route(routeName, routeParams), params, {
                fallbackFilename,
            });

            objectUrl = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            // Firefox only follows a click on an element that is in the document.
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (e) {
            toast.error(e?.message ?? 'The export could not be generated. Please try again.');
        } finally {
            // Revoked on the next frame: revoking synchronously after click()
            // can cut the download off before the browser has read the blob.
            if (objectUrl) setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            busy = false;
        }
    }
</script>

<Button {variant} {size} {title} onclick={download} disabled={disabled || busy} loading={busy}>
    <i class="ki-filled ki-exit-down"></i>
    {label}
</Button>
