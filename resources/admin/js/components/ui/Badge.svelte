<script>
    /** Badge — KTUI `kt-badge`. Variants map to semantic colors. */
    let { variant = 'secondary', outline = true, size = 'sm', class: klass = '', children } = $props();

    const variants = {
        primary: 'kt-badge-primary',
        secondary: 'kt-badge-secondary',
        success: 'kt-badge-success',
        warning: 'kt-badge-warning',
        info: 'kt-badge-info',
        destructive: 'kt-badge-destructive',
    };
    const sizes = { sm: 'kt-badge-sm', md: '', lg: 'kt-badge-lg' };
</script>

<span
    class="kt-badge {variants[variant] ?? variants.secondary} {outline ? 'kt-badge-outline' : ''} {sizes[size] ?? ''} {klass}"
>
    {@render children?.()}
</span>
