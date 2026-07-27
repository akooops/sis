<script>
    /**
     * TagsInput — a list of short strings as removable pills inside one kt-input.
     *
     *   <TagsInput bind:value={form.data.tags} dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'} />
     *
     * Enter, comma or a separated paste commit; Backspace on an empty field eats
     * the last pill. Duplicates are refused case-insensitively. `value` is always
     * a plain string array — how the server stores it is not this component's
     * business.
     */
    let {
        value = $bindable([]),
        placeholder,
        invalid = false,
        disabled = false,
        max = null,
        dir = 'ltr',
    } = $props();

    let draft = $state('');

    const tags = $derived(Array.isArray(value) ? value : []);

    /** Splits on comma/newline, so one paste can commit several pills. */
    function add(input) {
        const next = [...tags];

        for (const raw of String(input ?? '').split(/[,\r\n]+/)) {
            const tag = raw.trim();
            if (!tag) continue;
            if (max != null && next.length >= max) break;
            if (next.some((t) => t.toLowerCase() === tag.toLowerCase())) continue;
            next.push(tag);
        }

        if (next.length !== tags.length) value = next;
    }

    function remove(index) {
        value = tags.filter((_, i) => i !== index);
    }

    function onKeydown(event) {
        if (event.key === 'Enter' || event.key === ',') {
            // Enter would otherwise submit the form the control sits in.
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

    // Commit what's typed instead of dropping it when focus leaves.
    function onBlur() {
        if (!draft.trim()) return;
        add(draft);
        draft = '';
    }
</script>

<div
    {dir}
    class="kt-input h-auto min-h-[2.125rem] flex-wrap py-1 {invalid ? 'border-destructive' : ''} {disabled ? 'opacity-60' : ''}"
>
    {#each tags as tag, index (tag)}
        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary max-w-full">
            <span class="min-w-0 truncate" title={tag}>{tag}</span>
            <button type="button" class="ms-1 shrink-0" {disabled} onclick={() => remove(index)} aria-label="Remove {tag}">
                <i class="ki-filled ki-cross text-2xs"></i>
            </button>
        </span>
    {/each}

    <!-- basis-0 neutralises Metronic's `width:100%` on a nested input, which would
         otherwise push the field onto its own row as soon as a pill exists. -->
    <input
        type="text"
        class="min-w-[6rem] grow basis-0"
        bind:value={draft}
        {disabled}
        placeholder={tags.length ? '' : placeholder}
        autocomplete="off"
        aria-invalid={invalid}
        onkeydown={onKeydown}
        onpaste={onPaste}
        onblur={onBlur}
    />
</div>
