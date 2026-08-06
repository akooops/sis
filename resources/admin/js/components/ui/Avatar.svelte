<script>
    /** Avatar — image with initials fallback. */
    let { src = null, name = '', size = 'md', class: klass = '' } = $props();

    const sizes = {
        xs: 'size-6 text-2xs',
        sm: 'size-8 text-xs',
        md: 'size-10 text-sm',
        lg: 'size-12 text-base',
    };

    const initials = $derived(
        (name || '')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((p) => p[0]?.toUpperCase() ?? '')
            .join(''),
    );

    let failed = $state(false);
</script>

{#if src && !failed}
    <img
        {src}
        alt={name}
        class="rounded-full object-cover {sizes[size] ?? sizes.md} {klass}"
        onerror={() => (failed = true)}
    />
{:else}
    <span
        class="inline-flex items-center justify-center rounded-full bg-primary/10 font-medium text-primary {sizes[size] ?? sizes.md} {klass}"
    >
        {initials || '?'}
    </span>
{/if}
