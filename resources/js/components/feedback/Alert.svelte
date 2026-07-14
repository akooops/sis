<script>
    /** Alert — inline message banner using KTUI `kt-alert` variants. */
    let { variant = 'info', class: klass = '', children } = $props();

    const variants = {
        info: 'kt-alert-info',
        success: 'kt-alert-success',
        warning: 'kt-alert-warning',
        destructive: 'kt-alert-destructive',
        primary: 'kt-alert-primary',
    };
</script>

<div class="kt-alert {variants[variant] ?? variants.info} {klass}" role="alert">
    <div class="kt-alert-content">
        {@render children?.()}
    </div>
</div>
