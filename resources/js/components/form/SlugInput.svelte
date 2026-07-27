<script>
    /**
     * SlugInput — a slug field that fills itself from another field until the user
     * takes over.
     *
     *   <SlugInput bind:value={form.data.slug} source={form.data.name} />
     *
     * Auto-fill stops the moment the field is edited by hand, and never starts on
     * an existing record: a saved slug is a live URL, so renaming the record must
     * not quietly move it. Clearing the field re-arms auto-fill, which is the
     * escape hatch if you took it over by accident.
     */
    import { untrack } from 'svelte';

    let {
        value = $bindable(''),
        source = '',
        disabled = false,
        invalid = false,
        placeholder = null,
        ...rest
    } = $props();

    /**
     * Matches the server's `^[a-z0-9]+(?:-[a-z0-9]+)*$`.
     *
     * NFKD splits an accented letter into base + combining mark, and the
     * [^a-z0-9] pass then drops the mark on its own — so "Café" lands on "cafe"
     * rather than "caf", with no separate diacritic step.
     */
    function slugify(input) {
        return String(input ?? '')
            .normalize('NFKD')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Seeded from the value the form started with: an edit form arrives with a
    // slug already set, so it counts as taken over from the first render.
    let touched = $state(!!untrack(() => value));

    $effect(() => {
        const next = slugify(source);

        if (touched || disabled) return;
        if (next === untrack(() => value)) return;

        value = next;
    });

    function onInput(event) {
        value = event.currentTarget.value;
        // An empty field means "you choose again", so auto-fill resumes.
        touched = value !== '';
    }
</script>

<input
    type="text"
    class="kt-input {invalid ? 'border-destructive' : ''}"
    {value}
    {disabled}
    {placeholder}
    oninput={onInput}
    aria-invalid={invalid}
    {...rest}
/>
