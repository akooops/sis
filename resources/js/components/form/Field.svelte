<script>
    /**
     * Field — label + control + error/hint wrapper. Wrap any input:
     *   <Field label="Email" error={form.errors.email} required>
     *       <Input bind:value={form.data.email} />
     *   </Field>
     */
    let { label = null, error = null, hint = null, required = false, for: forId = null, class: klass = '', children } = $props();
</script>

<div class="flex flex-col gap-1.5 {klass}">
    {#if label}
        <label class="kt-form-label font-normal text-mono" for={forId}>
            {label}{#if required}<span class="text-destructive ms-0.5">*</span>{/if}
        </label>
    {/if}

    {@render children?.()}

    {#if error}
        <span class="text-xs text-destructive">{error}</span>
    {:else if hint}
        <span class="text-xs text-muted-foreground">{hint}</span>
    {/if}
</div>
