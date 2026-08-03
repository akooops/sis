<script>
    /**
     * Setting edit — one control, and only `value`.
     *
     * Everything else on the row (group, key, type, whether it holds a list) is
     * seeded metadata the API refuses to take, so it is drawn as context rather
     * than as inputs the server would ignore. The control follows the pair
     * (type, is_multiple): each of the five types maps to one widget, and a list
     * setting either repeats that widget or switches the picker to multi-select.
     * Nothing below names a group or a key, so the catalogue can grow in config
     * without touching this file.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';
    import { settingModelConfig, settingResolvedOptions, settingTypeLabel } from '@/lib/setting';

    let { setting, onsaved, oncancel } = $props();

    // The row is fixed for the life of this form — the index unmounts it between
    // edits — so the shape is settled once here rather than re-derived per render.
    const model = settingModelConfig(setting);

    /**
     * Which widget family renders. A model setting whose alias has left the
     * registry degrades to the raw id instead of a picker with no route to
     * search: still editable, just not browsable.
     */
    const control = setting.type === 'model' && !model ? 'text' : setting.type;

    // …and that box only ever accepts being emptied: with no registry entry there
    // is no table to check an id against, so the server rejects every one of them
    // (UpdateSettingData::reference -> Rule::in([])). Say so on the field rather
    // than letting the admin discover it by typing a valid id and being refused.
    const degraded = setting.type === 'model' && !model;

    // text/number/date repeat their control; select and model already have a
    // multi-select mode, so they switch instead of stacking.
    const repeatable = setting.is_multiple && ['text', 'number', 'date'].includes(control);

    // A picker/calendar clears to null; a text box clears to ''.
    const blank = control === 'text' || control === 'number' ? '' : null;

    const form = useForm({
        value: setting.is_multiple
            ? Array.isArray(setting.value)
                ? [...setting.value]
                : []
            : (setting.value ?? blank),
    });

    // Seeds the picker with the labels the index already resolved, so the form
    // opens on names rather than ULIDs and never re-fetches to find that out.
    const initialOptions = settingResolvedOptions(setting);

    const valueLabel = setting.is_multiple ? 'Values' : 'Value';

    /**
     * A member error (`value.0`) has a control of its own only in the repeatable
     * branch — a multi-select's chips have nowhere to hang one. Surfacing the
     * first on the field itself is what keeps a rejected entry from reading as a
     * Save button that does nothing: a 422 shows no toast by design.
     */
    const memberError = $derived(
        Object.entries(form.errors).find(([key]) => key.startsWith('value.'))?.[1] ?? null,
    );
    const valueError = $derived(form.errors.value ?? (repeatable ? null : memberError));

    const valueHint = $derived(
        [
            setting.description,
            degraded
                ? `This setting points at “${setting.model_type ?? 'an unknown record type'}”, which is no longer a configurable record type — the value can only be cleared here.`
                : null,
        ]
            .filter(Boolean)
            .join(' ') || null,
    );

    function addRow() {
        form.data.value = [...form.data.value, blank];
    }

    function removeRow(index) {
        form.data.value = form.data.value.filter((_, i) => i !== index);
    }

    async function submit(event) {
        event.preventDefault();

        // Drop the rows the admin added and never filled BEFORE submitting rather
        // than in the transform: `value.*` is required, and pruning them on the
        // way out would shift every index the server's errors point back at.
        if (repeatable) {
            form.data.value = form.data.value.filter(
                (v) => v !== null && v !== undefined && String(v).trim() !== '',
            );
        }

        try {
            const res = await form.submit('put', route('api.v1.admin.settings.update', setting.id), {
                // A cleared box means the setting is unset, not an empty string
                // for the `numeric`/`date` rules to reject.
                transform: (data) => ({ value: data.value === '' ? null : data.value }),
            });

            if (res) {
                toast.success('Updated successfully.');
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <!-- Read-only context: what is being changed, and where it lives. -->
    <div class="flex flex-col gap-1.5 border-b border-border pb-5">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-base font-semibold text-mono">{setting.name}</h3>
            <Badge variant="secondary">{settingTypeLabel(setting)}</Badge>
            {#if model}<Badge variant="secondary">{model.name}</Badge>{/if}
        </div>
        <span class="font-mono text-2sm text-muted-foreground">{setting.group}.{setting.key}</span>
    </div>

    <Field label={valueLabel} error={valueError} hint={valueHint}>
        {#if repeatable}
            <div class="flex flex-col gap-2">
                {#each form.data.value as _, index (index)}
                    <div class="flex items-start gap-2">
                        <div class="min-w-0 grow">
                            {#if control === 'date'}
                                <DatePicker
                                    bind:value={form.data.value[index]}
                                    invalid={!!form.errors[`value.${index}`]}
                                />
                            {:else if control === 'number'}
                                <Input
                                    type="number"
                                    step="any"
                                    bind:value={form.data.value[index]}
                                    invalid={!!form.errors[`value.${index}`]}
                                />
                            {:else}
                                <Input
                                    bind:value={form.data.value[index]}
                                    invalid={!!form.errors[`value.${index}`]}
                                />
                            {/if}
                            {#if form.errors[`value.${index}`]}
                                <span class="mt-1 block text-xs text-destructive">{form.errors[`value.${index}`]}</span>
                            {/if}
                        </div>
                        <button
                            type="button"
                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 text-destructive"
                            onclick={() => removeRow(index)}
                            aria-label="Remove entry"
                        >
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </div>
                {/each}

                {#if form.data.value.length === 0}
                    <p class="rounded-lg border border-dashed border-border px-3 py-4 text-center text-xs text-muted-foreground">
                        This list is empty. Saving it that way leaves the setting unset.
                    </p>
                {/if}

                <div>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" onclick={addRow}>
                        <i class="ki-filled ki-plus"></i>Add entry
                    </button>
                </div>
            </div>
        {:else if control === 'select'}
            <Select
                options={setting.options ?? []}
                bind:value={form.data.value}
                multiple={setting.is_multiple}
                invalid={!!valueError}
                placeholder="Choose a value"
            />
        {:else if control === 'model'}
            <!-- resourceParams carries the setting's own `filter` to the same
                 index endpoint the server validates against, so the picker offers
                 exactly the records SettingReference would accept. -->
            <Select
                resource={model.resource}
                labelKey={model.labelKey}
                resourceParams={setting.filter ? { filter: setting.filter } : {}}
                bind:value={form.data.value}
                multiple={setting.is_multiple}
                {initialOptions}
                invalid={!!valueError}
                placeholder="Search {model.name.toLowerCase()}…"
            />
        {:else if control === 'date'}
            <DatePicker bind:value={form.data.value} invalid={!!valueError} />
        {:else if control === 'number'}
            <Input type="number" step="any" bind:value={form.data.value} invalid={!!valueError} />
        {:else}
            <Input bind:value={form.data.value} invalid={!!valueError} />
        {/if}
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
