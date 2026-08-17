<script>
    /**
     * A free-text tag list — the public sibling of the admin's
     * components/form/TagsInput.svelte.
     *
     * A COPY, NOT AN IMPORT, and deliberately so: `resources/site` may not import
     * from `resources/admin` or the whole Metronic theme lands on a public page.
     * The admin original is built out of `kt-input` and `kt-badge`; this one uses
     * the form renderer's own `.sisf-*` classes, which is most of what differs.
     *
     * Behaviour matches TagsType::store() on purpose — trimmed, blank-dropped and
     * deduplicated case-insensitively — so what the visitor sees committed is
     * what gets stored, rather than the server quietly removing a pill they can
     * still see on screen.
     */
    let {
        field,
        value = [],
        id = null,
        placeholder = '',
        disabled = false,
        invalid = false,
        describedBy = null,
        labels = {},
        onchange = null,
        onfocus = null,
        onblur = null,
    } = $props();

    let draft = $state('');

    const tags = $derived(Array.isArray(value) ? value : []);

    const max = $derived(Number(field?.validation?.max_items ?? 20) || 20);

    function commit(next) {
        onchange?.(field, next);
    }

    /** Splits on comma/newline, so one paste can commit several pills. */
    function add(input) {
        const next = [...tags];

        for (const raw of String(input ?? '').split(/[,\r\n]+/)) {
            const tag = raw.trim();

            if (!tag) continue;
            if (next.length >= max) break;
            if (next.some((t) => t.toLowerCase() === tag.toLowerCase())) continue;

            next.push(tag);
        }

        if (next.length !== tags.length) commit(next);
    }

    function remove(index) {
        commit(tags.filter((_, i) => i !== index));
    }

    function onKeydown(event) {
        if (event.key === 'Enter' || event.key === ',') {
            // Enter would otherwise submit the form the control sits in — and on
            // a multi-page form that means submitting from step one.
            event.preventDefault();
            add(draft);
            draft = '';

            return;
        }

        if (event.key === 'Backspace' && draft === '' && tags.length) {
            event.preventDefault();
            remove(tags.length - 1);
        }
    }

    function onPaste(event) {
        const text = event.clipboardData?.getData('text') ?? '';

        // A single value keeps the normal paste — only a separated one splits.
        if (!/[,\r\n]/.test(text)) return;

        event.preventDefault();
        add(text);
    }

    /** Commit what is typed instead of dropping it when focus leaves. */
    function handleBlur(event) {
        if (draft.trim()) {
            add(draft);
            draft = '';
        }

        onblur?.(field, event);
    }
</script>

<div class="sisf-tags {invalid ? 'is-invalid' : ''}">
    {#each tags as tag, index (tag)}
        <span class="sisf-tag">
            <span class="sisf-tag-text" title={tag}>{tag}</span>
            <button
                type="button"
                class="sisf-tag-remove"
                {disabled}
                onclick={() => remove(index)}
                aria-label={`${labels.groupRemove ?? 'Remove'} ${tag}`}
            >&times;</button>
        </span>
    {/each}

    <input
        {id}
        type="text"
        class="sisf-tags-input"
        bind:value={draft}
        {disabled}
        placeholder={tags.length ? '' : placeholder}
        autocomplete="off"
        aria-invalid={invalid}
        aria-describedby={describedBy}
        onkeydown={onKeydown}
        onpaste={onPaste}
        onfocus={(e) => onfocus?.(field, e)}
        onblur={handleBlur}
    />
</div>
