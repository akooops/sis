<script>
    /**
     * PasswordInput — password field with a show/hide eye toggle, Svelte-driven
     * (so it works inside client-rendered form panels, unlike KTUI's
     * data-kt-toggle-password which only initialises on page load).
     */
    let {
        value = $bindable(''),
        invalid = false,
        disabled = false,
        placeholder = '',
        autocomplete = 'current-password',
        id = undefined,
        ...rest
    } = $props();

    let show = $state(false);
</script>

<div class="kt-input {invalid ? 'kt-input-error border-destructive' : ''}">
    <input
        {id}
        type={show ? 'text' : 'password'}
        {placeholder}
        {autocomplete}
        {disabled}
        value={value}
        oninput={(e) => (value = e.currentTarget.value)}
        {...rest}
    />
    <button
        type="button"
        class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5"
        onclick={() => (show = !show)}
        aria-label="Toggle password visibility"
        tabindex="-1"
    >
        <i class="ki-filled {show ? 'ki-eye-slash' : 'ki-eye'} text-muted-foreground"></i>
    </button>
</div>
