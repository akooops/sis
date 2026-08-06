<script>
    /**
     * Button — thin wrapper over KTUI's `kt-btn` classes with variant/size props
     * and a built-in loading spinner. Svelte 5 runes; use the `onclick` prop.
     */
    import Spinner from './Spinner.svelte';

    let {
        variant = 'primary',
        size = 'md',
        type = 'button',
        disabled = false,
        loading = false,
        block = false,
        class: klass = '',
        children,
        ...rest
    } = $props();

    const variants = {
        primary: 'kt-btn-primary',
        secondary: 'kt-btn-secondary',
        outline: 'kt-btn-outline',
        ghost: 'kt-btn-ghost',
        destructive: 'kt-btn-destructive',
        mono: 'kt-btn-mono',
        success: 'kt-btn-success',
    };

    const sizes = {
        sm: 'kt-btn-sm',
        md: '',
        lg: 'kt-btn-lg',
        icon: 'kt-btn-icon',
    };
</script>

<button
    {type}
    class="kt-btn {variants[variant] ?? ''} {sizes[size] ?? ''} {block ? 'w-full justify-center' : ''} {klass}"
    disabled={disabled || loading}
    aria-busy={loading}
    {...rest}
>
    {#if loading}
        <Spinner size={size === 'lg' ? 'md' : 'sm'} />
    {/if}
    {@render children?.()}
</button>
