<script>
    /**
     * SchemaField — renders one driver-declared field (FieldData) into the right
     * control.
     *
     * Two consumers with opposite needs:
     *  - IntegrationForm binds (`bind:value`) and lets the control write straight
     *    back into its values object.
     *  - The forms builder's inspector must NOT bind — writing into the canvas
     *    document from here would re-enter the effect that renders it — so it
     *    passes `value` plus `onchange` and applies the patch itself.
     *
     * Both work because `value` stays $bindable (an unbound $bindable falls back
     * to local state) and every change is also announced through `onchange`. The
     * lastKey guard is seeded from the initial value, so mounting is silent and a
     * parent echoing the same value back cannot ping-pong.
     *
     * Secrets are write-only: a stored secret is never sent back, so the field
     * shows empty with a "leave blank to keep current" placeholder. The parent
     * submits the value as-is — the backend keeps the stored secret when it's
     * blank, and only overwrites it when a new value is typed.
     */
    import Field from './Field.svelte';
    import Input from './Input.svelte';
    import PasswordInput from './PasswordInput.svelte';
    import Select from './Select.svelte';
    import Switch from './Switch.svelte';
    import TagsInput from './TagsInput.svelte';
    import ColorInput from './ColorInput.svelte';

    let {
        field,
        value = $bindable(),
        error = null,
        secretSet = false,
        disabled = false,
        onchange = null,
    } = $props();

    const options = $derived((field.options ?? []).map((o) => ({ value: o.value, label: o.label })));
    const inputType = $derived({ number: 'number', email: 'email', url: 'url' }[field.type] ?? 'text');

    // Bounds belong to the number control and nowhere else: min on a text box
    // would be a length the browser never reports and the server never applies.
    // Built as an object so an undeclared bound is an ABSENT attribute — an
    // emitted step="null" is invalid and the browser falls back to step 1, which
    // is the very default this exists to override.
    const numberAttrs = $derived.by(() => {
        if (field.type !== 'number') return {};

        const attrs = {};
        if (field.min != null) attrs.min = field.min;
        if (field.max != null) attrs.max = field.max;
        if (field.step != null) attrs.step = field.step;
        return attrs;
    });
    // A secret that already has a stored value isn't "required" — leaving it
    // blank keeps the current one.
    const required = $derived(field.required && !(field.secret && secretSet));

    // Object values (tags) may be mutated in place, so compare by content rather
    // than reference. Plain, not $state: tracking it would re-run the effect.
    const keyOf = (v) => (v !== null && typeof v === 'object' ? JSON.stringify(v) : `${typeof v}:${v}`);
    let lastKey = keyOf(value);

    $effect(() => {
        const next = value;
        const key = keyOf(next);

        if (key === lastKey) return;

        lastKey = key;
        onchange?.(next);
    });
</script>

{#if field.type === 'switch'}
    <Field label={field.label} error={error} hint={field.help}>
        <div class="flex items-center"><Switch bind:value {disabled} /></div>
    </Field>
{:else}
    <Field label={field.label} error={error} hint={field.help} {required}>
        {#if field.secret}
            <PasswordInput
                bind:value
                invalid={!!error}
                {disabled}
                autocomplete="new-password"
                placeholder={secretSet ? 'Leave blank to keep current' : ''}
            />
        {:else if field.type === 'select'}
            <Select
                {options}
                bind:value
                invalid={!!error}
                {disabled}
                clearable={!field.required}
                placeholder={field.label}
            />
        {:else if field.type === 'multiselect'}
            <!-- Options-driven, so the admin PICKS rather than types: a tags box
                 would happily accept a value the server refuses. Empty is a
                 legitimate answer (each schema's `help` says what it means), so
                 it stays clearable unless the field is required. -->
            <Select
                {options}
                bind:value
                multiple
                invalid={!!error}
                {disabled}
                clearable={!field.required}
                placeholder={field.label}
            />
        {:else if field.type === 'textarea'}
            <textarea
                class="kt-input min-h-[90px]"
                class:border-destructive={!!error}
                {disabled}
                bind:value
            ></textarea>
        {:else if field.type === 'tags'}
            <TagsInput bind:value invalid={!!error} {disabled} placeholder={field.help ?? ''} />
        {:else if field.type === 'color'}
            <ColorInput bind:value invalid={!!error} />
        {:else}
            <Input type={inputType} bind:value invalid={!!error} {disabled} {...numberAttrs} />
        {/if}
    </Field>
{/if}
