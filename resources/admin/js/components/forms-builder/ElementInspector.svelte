<script>
    /**
     * Settings for whatever is selected — a PAGE or an ELEMENT.
     *
     * ONE inspector, two modes. A page and an element are edited in exactly the
     * same place, in the same visual language: sections separated by a rule, not
     * cards. A second component for pages meant two panels that drifted apart on
     * spacing, headings and control order, for two things that are the same job.
     *
     * ONE-WAY into the document. Every control takes `value` and reports through
     * `onchange`; nothing binds. A two-way bind here would write into the same
     * $state the canvas renders from, and the effect that re-renders the canvas
     * would feed straight back into the control — a loop that shows up as an
     * input you cannot type in.
     *
     * Settings and validation controls are rendered from the SERVER's declared
     * schema through SchemaField — the same component the integrations form uses
     * for driver schemas. Adding an element type needs no work here.
     *
     * The page's DELETE is NOT here. It lives on the page header on the canvas,
     * next to the thing it removes.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import SchemaField from '@/components/form/SchemaField.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';

    let {
        field = null,
        page = null,
        index = 0,
        spec = null,
        pages = [],
        locale = 'en',
        isRtl = false,
        isDefaultLocale = true,
        locked = false,
        errors = null,
        onpatch = null,
        onaddoption = null,
        onremoveoption = null,
        onpatchoption = null,
    } = $props();

    const dir = $derived(isRtl ? 'rtl' : 'ltr');

    /*
     * A page and an element are never both selected — the builder clears one
     * when it sets the other. `target` is whichever it is, so the translatable
     * helper below is written once rather than once per mode.
     */
    const target = $derived(page ?? field);

    /** Patch one locale of a translatable map without touching the others. */
    function setLocalised(key, value) {
        onpatch?.(target.id, { [key]: { ...(target[key] ?? {}), [locale]: value } });
    }

    function setSetting(bag, key, value) {
        onpatch?.(field.id, { [bag]: { ...(field[bag] ?? {}), [key]: value } });
    }

    const translatable = $derived(spec?.translatable ?? []);
    const showsContent = $derived(translatable.includes('content'));

    const pageSelector = $derived(page?.css_id ? `#${page.css_id}` : `.${page?.css_class}`);
</script>

{#if page}
    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-medium text-mono">Page</h3>
            <code class="text-2sm text-muted-foreground">Page {index + 1}</code>
        </div>

        <Field label="Name" required error={errors?.name} hint="What this page is called in the builder.">
            <Input
                value={page.name}
                disabled={locked}
                invalid={!!errors?.name}
                oninput={(e) => onpatch?.(page.id, { name: e.currentTarget.value })}
            />
        </Field>

        <!-- Translatable copy, for the locale the canvas is showing. The same
             per-locale patch an element's label gets: one locale of the map is
             replaced and the rest are left alone. -->
        <Field label="Title" hint="Shown at the top of the page." error={errors?.title}>
            <Input
                value={page.title?.[locale] ?? ''}
                {dir}
                disabled={locked}
                oninput={(e) => setLocalised('title', e.currentTarget.value)}
            />
        </Field>

        <div class="flex flex-col gap-4 border-t border-border pt-4">
            <span class="text-2sm font-medium">Behaviour</span>

            <Field
                label="Instruction page"
                error={errors?.is_interstitial}
                hint="Entered from a button and returned from — it is not a step, so it never appears in the progress count."
            >
                <Switch
                    value={!!page.is_interstitial}
                    disabled={locked}
                    onchange={(v) => onpatch?.(page.id, { is_interstitial: v })}
                />
            </Field>
        </div>

        <!-- The page's own handles, the same pair an element carries. A form is
             styled from its stylesheet and nothing else, so these are the only
             way the page itself can be targeted by name. -->
        <div class="flex flex-col gap-4 border-t border-border pt-4">
            <span class="text-2sm font-medium">Styling</span>

            <Field label="CSS class" error={errors?.css_class}>
                <Input
                    value={page.css_class ?? ''}
                    disabled={locked}
                    placeholder="my-page"
                    oninput={(e) => onpatch?.(page.id, { css_class: e.currentTarget.value })}
                />
            </Field>

            <Field label="CSS id" error={errors?.css_id}>
                <Input
                    value={page.css_id ?? ''}
                    disabled={locked}
                    placeholder="my-id"
                    oninput={(e) => onpatch?.(page.id, { css_id: e.currentTarget.value })}
                />
            </Field>

            {#if page.css_class || page.css_id}
                <div class="rounded-lg bg-muted/50 p-3 text-2sm">
                    <p class="mb-1 text-muted-foreground">Target it from the form's stylesheet with:</p>
                    <code class="block">{pageSelector}</code>
                    <code class="block">{pageSelector} .sisf-page-title</code>
                </div>
            {/if}
        </div>
    </div>
{:else if field}
    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-medium text-mono">{spec?.label ?? field.type}</h3>
            <code class="text-2sm text-muted-foreground">{field.key}</code>
        </div>

        <!-- Translatable copy, for the locale the canvas is showing. -->
        {#if showsContent}
            <Field label="Content" hint="Shown to the visitor." error={errors?.content}>
                <textarea
                    class="kt-input min-h-[90px]"
                    {dir}
                    disabled={locked}
                    value={field.content?.[locale] ?? ''}
                    oninput={(e) => setLocalised('content', e.currentTarget.value)}
                ></textarea>
            </Field>
        {/if}

        {#if translatable.includes('label')}
            <Field label="Label" required={isDefaultLocale} error={errors?.label}>
                <Input
                    value={field.label?.[locale] ?? ''}
                    {dir}
                    disabled={locked}
                    oninput={(e) => setLocalised('label', e.currentTarget.value)}
                />
            </Field>
        {/if}

        {#if translatable.includes('placeholder')}
            <Field label="Placeholder">
                <Input
                    value={field.placeholder?.[locale] ?? ''}
                    {dir}
                    disabled={locked}
                    oninput={(e) => setLocalised('placeholder', e.currentTarget.value)}
                />
            </Field>
        {/if}

        {#if translatable.includes('value')}
            <Field label="Value" hint="Pre-filled before the visitor answers.">
                <Input
                    value={field.value?.[locale] ?? ''}
                    {dir}
                    disabled={locked}
                    oninput={(e) => setLocalised('value', e.currentTarget.value)}
                />
            </Field>
        {/if}

        <!-- Options: values are never translated, labels always are. -->
        {#if spec?.has_options}
            <div class="flex flex-col gap-2 border-t border-border pt-4">
                <div class="flex items-center justify-between">
                    <span class="text-2sm font-medium">Options</span>
                    {#if !locked}
                        <button type="button" class="kt-btn kt-btn-xs kt-btn-secondary" onclick={() => onaddoption?.(field.id)}>
                            <i class="ki-filled ki-plus"></i>Add
                        </button>
                    {/if}
                </div>
                {#if errors?.options}<p class="text-2sm text-destructive">{errors.options}</p>{/if}

                {#each field.options ?? [] as option (option.id)}
                    <div class="flex items-center gap-2">
                        <Input
                            value={option.label?.[locale] ?? ''}
                            {dir}
                            disabled={locked}
                            placeholder="Label"
                            oninput={(e) => onpatchoption?.(field.id, option.id, { label: { ...(option.label ?? {}), [locale]: e.currentTarget.value } })}
                        />
                        <Input
                            value={option.value}
                            disabled={locked}
                            placeholder="value"
                            oninput={(e) => onpatchoption?.(field.id, option.id, { value: e.currentTarget.value })}
                        />
                        {#if !locked}
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-destructive shrink-0"
                                aria-label="Remove option"
                                onclick={() => onremoveoption?.(field.id, option.id)}
                            ><i class="ki-filled ki-cross"></i></button>
                        {/if}
                    </div>
                {/each}
            </div>
        {/if}

        <!-- Behaviour, from the element's own declared schema. -->
        {#if spec?.settings?.length}
            <div class="flex flex-col gap-4 border-t border-border pt-4">
                <span class="text-2sm font-medium">Behaviour</span>
                {#each spec.settings as setting (setting.key)}
                    <SchemaField
                        field={setting}
                        value={field.settings?.[setting.key] ?? setting.default ?? null}
                        disabled={locked}
                        onchange={(v) => setSetting('settings', setting.key, v)}
                    />
                {/each}

                {#if field.type === 'button' && field.settings?.action === 'goto'}
                    <Field label="Go to page" required error={errors?.target_form_page_id}>
                        <select
                            class="kt-input"
                            disabled={locked}
                            value={field.target_form_page_id ?? ''}
                            onchange={(e) => onpatch?.(field.id, { target_form_page_id: e.currentTarget.value || null })}
                        >
                            <option value="">Pick a page…</option>
                            {#each pages as target (target.id)}
                                <option value={target.id}>{target.name}</option>
                            {/each}
                        </select>
                    </Field>
                {/if}
            </div>
        {/if}

        <!-- Validation, likewise. -->
        {#if spec?.is_input}
            <div class="flex flex-col gap-4 border-t border-border pt-4">
                <span class="text-2sm font-medium">Validation</span>

                <Field label="Required">
                    <Switch value={!!field.is_required} disabled={locked} onchange={(v) => onpatch?.(field.id, { is_required: v })} />
                </Field>

                <Field label="Must be unique" hint="Refuses an answer someone has already given on this form.">
                    <Switch value={!!field.is_unique} disabled={locked} onchange={(v) => onpatch?.(field.id, { is_unique: v })} />
                </Field>

                {#each spec.validations ?? [] as rule (rule.key)}
                    <SchemaField
                        field={rule}
                        value={field.validation?.[rule.key] ?? rule.default ?? null}
                        disabled={locked}
                        onchange={(v) => setSetting('validation', rule.key, v)}
                    />
                {/each}
            </div>
        {/if}

        <!-- The CSS hooks. These land on the element's CONTAINER. -->
        <div class="flex flex-col gap-4 border-t border-border pt-4">
            <span class="text-2sm font-medium">Styling</span>

            <Field label="CSS class" error={errors?.css_class}>
                <Input
                    value={field.css_class ?? ''}
                    disabled={locked}
                    placeholder="my-class"
                    oninput={(e) => onpatch?.(field.id, { css_class: e.currentTarget.value })}
                />
            </Field>

            <Field label="CSS id" error={errors?.css_id}>
                <Input
                    value={field.css_id ?? ''}
                    disabled={locked}
                    placeholder="my-id"
                    oninput={(e) => onpatch?.(field.id, { css_id: e.currentTarget.value })}
                />
            </Field>

            {#if field.css_class || field.css_id}
                <div class="rounded-lg bg-muted/50 p-3 text-2sm">
                    <p class="mb-1 text-muted-foreground">Target it from the form's stylesheet with:</p>
                    <code class="block">{field.css_id ? `#${field.css_id}` : `.${field.css_class}`} input</code>
                    <code class="block">{field.css_id ? `#${field.css_id}` : `.${field.css_class}`} .sisf-label</code>
                </div>
            {/if}
        </div>

        <Field label="Machine key" hint="The answer's name — in exports, in webhooks, everywhere." error={errors?.key}>
            <Input
                value={field.key}
                disabled={locked}
                oninput={(e) => onpatch?.(field.id, { key: e.currentTarget.value })}
            />
        </Field>
    </div>
{:else}
    <EmptyState
        icon="ki-filled ki-cursor"
        title="Nothing selected"
        body="Pick a page or an element on the canvas to edit it."
    />
{/if}
