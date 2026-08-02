<script>
    /**
     * A file upload.
     *
     * The VALUE is a media id (or a list of them), never the bytes: files go to
     * their own endpoint first and the form submits the ids. That is what lets
     * the same answer survive a page change, and what keeps a virus scan out of
     * the submit request.
     *
     * The upload itself is injected as `onupload` rather than performed here —
     * the endpoint differs between the public page and the preview (which
     * uploads nothing at all), and a leaf component must not know about either.
     */
    let {
        field,
        value = null,
        disabled = false,
        id = undefined,
        invalid = false,
        describedBy = undefined,
        onchange = null,
        onfocus = null,
        onblur = null,
        onupload = null,
    } = $props();

    const multiple = $derived(!!field?.settings?.is_multiple);
    const maxFiles = $derived(Number(field?.settings?.max_files) > 0 ? Number(field.settings.max_files) : 3);
    const accept = $derived(
        (field?.settings?.extensions ?? []).map((e) => `.${String(e).replace(/^\./, '')}`).join(',') || undefined,
    );

    /** Always a list internally; only the emitted value differs. */
    const items = $derived(multiple ? (Array.isArray(value) ? value : []) : value ? [value] : []);

    let busy = $state(false);
    let notice = $state('');

    function emit(next) {
        onchange?.(field, multiple ? next : (next[0] ?? null));
    }

    async function pick(event) {
        const chosen = Array.from(event.currentTarget.files ?? []);
        event.currentTarget.value = '';

        if (!chosen.length) return;

        if (!onupload) {
            // The builder preview has nowhere to upload to; say so rather than
            // silently doing nothing.
            notice = 'Uploads are disabled in preview.';

            return;
        }

        const room = multiple ? maxFiles - items.length : 1;

        if (room <= 0) {
            notice = `Up to ${maxFiles} file(s).`;

            return;
        }

        busy = true;
        notice = '';

        try {
            const uploaded = [];

            for (const file of chosen.slice(0, room)) {
                const media = await onupload(field, file);
                if (media?.id) uploaded.push({ id: media.id, name: media.name ?? file.name });
            }

            emit(multiple ? [...items, ...uploaded] : uploaded);
        } catch (e) {
            notice = e?.message ?? 'That file could not be uploaded.';
        } finally {
            busy = false;
        }
    }

    function drop(index) {
        emit(items.filter((_, i) => i !== index));
    }
</script>

<input
    {id}
    type="file"
    class="sisf-file"
    {accept}
    {multiple}
    disabled={disabled || busy}
    required={!!field?.is_required && items.length === 0}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    onchange={pick}
    onfocus={() => onfocus?.(field)}
    onblur={() => onblur?.(field)}
/>

{#if busy}<p class="sisf-file-status">Uploading…</p>{/if}
{#if notice}<p class="sisf-file-status">{notice}</p>{/if}

{#if items.length}
    <ul class="sisf-file-list">
        {#each items as item, index (item.id ?? index)}
            <li class="sisf-file-item">
                <span class="sisf-file-name">{item.name ?? item.id ?? item}</span>
                <button
                    type="button"
                    class="sisf-file-remove"
                    {disabled}
                    aria-label="Remove file"
                    onclick={() => drop(index)}
                >×</button>
            </li>
        {/each}
    </ul>
{/if}
