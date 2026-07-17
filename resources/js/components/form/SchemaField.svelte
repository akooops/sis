<script>
    /**
     * SchemaField — renders one driver-declared field (FieldData) into the right
     * control, binding into the parent form's values object.
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

    let { field, value = $bindable(), error = null, secretSet = false } = $props();

    const options = $derived((field.options ?? []).map((o) => ({ value: o.value, label: o.label })));
    const inputType = $derived({ number: 'number', email: 'email', url: 'url' }[field.type] ?? 'text');
    // A secret that already has a stored value isn't "required" — leaving it
    // blank keeps the current one.
    const required = $derived(field.required && !(field.secret && secretSet));
</script>

{#if field.type === 'switch'}
    <Field label={field.label} error={error} hint={field.help}>
        <div class="flex items-center"><Switch bind:value /></div>
    </Field>
{:else}
    <Field label={field.label} error={error} hint={field.help} {required}>
        {#if field.secret}
            <PasswordInput
                bind:value
                invalid={!!error}
                autocomplete="new-password"
                placeholder={secretSet ? 'Leave blank to keep current' : ''}
            />
        {:else if field.type === 'select'}
            <Select {options} bind:value invalid={!!error} clearable={!field.required} placeholder={field.label} />
        {:else if field.type === 'textarea'}
            <textarea class="kt-input min-h-[90px]" class:border-destructive={!!error} bind:value></textarea>
        {:else}
            <Input type={inputType} bind:value invalid={!!error} />
        {/if}
    </Field>
{/if}
