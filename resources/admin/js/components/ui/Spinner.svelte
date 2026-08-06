<script>
    /** Spinner — small inline loading indicator (pure CSS, no deps). */
    let { size = 'md', class: klass = '' } = $props();

    const sizes = { sm: 'size-3.5 border-2', md: 'size-5 border-2', lg: 'size-7 border-[3px]' };
</script>

<span
    class="inline-block animate-spin rounded-full border-current border-t-transparent align-[-0.125em] {sizes[size] ?? sizes.md} {klass}"
    role="status"
    aria-label="Loading"
></span>
