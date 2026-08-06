<script>
    /**
     * Integration create/edit form. Owns the whole add flow so it can be dropped
     * into PivotDrawer's single `form` slot: adding shows a driver picker first,
     * then the credential fields; editing goes straight to the fields (the driver
     * is already known). Fields come from the driver's schema; secrets stay
     * write-only (a stored secret is never sent back).
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SchemaField from '@/components/form/SchemaField.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { type, drivers = [], integration = null, onsaved, oncancel } = $props();

    const editing = !!integration;

    // Editing: the driver is fixed by the record. Adding: null until picked.
    let chosenDriver = $state(
        editing ? (drivers.find((d) => d.code === integration.driver) ?? { code: integration.driver, name: integration.driver, icon: '', schema: [] }) : null,
    );

    const schema = $derived(chosenDriver?.schema ?? []);
    const secretSet = integration?.secrets_set ?? {};

    let name = $state(integration?.name ?? '');
    // Seeded SYNCHRONOUSLY from the driver, never via an effect: a field binds
    // `values[key]`, and if that key is momentarily undefined (the gap an effect
    // leaves on first render) the bind throws props_invalid_value.
    let values = $state(seed(chosenDriver));
    let errors = $state({});
    let processing = $state(false);

    function seed(driver) {
        const next = {};
        for (const f of driver?.schema ?? []) {
            if (f.type === 'switch') {
                next[f.key] = editing && !f.secret ? !!integration.config?.[f.key] : !!f.default;
            } else if (f.secret) {
                next[f.key] = ''; // never seed a secret back
            } else if (editing) {
                next[f.key] = integration.config?.[f.key] ?? f.default ?? '';
            } else {
                next[f.key] = f.default ?? '';
            }
        }
        return next;
    }

    function pickDriver(d) {
        chosenDriver = d;
        values = seed(d); // reseed for the new schema before its fields render
    }

    // Back: from the fields of a NEW integration, return to the driver picker;
    // otherwise (editing, or already on the picker) leave the form entirely.
    function back() {
        if (!editing && chosenDriver) {
            chosenDriver = null;
        } else {
            oncancel?.();
        }
    }

    async function save(event) {
        event.preventDefault();
        processing = true;
        errors = {};
        try {
            let res;
            if (editing) {
                res = await api.put(route('api.v1.admin.integrations.update', integration.id), { name, settings: values });
            } else {
                res = await api.post(route('api.v1.admin.integrations.store'), {
                    integration_type_id: type.id,
                    driver: chosenDriver.code,
                    name,
                    settings: values,
                });
            }
            toast.success('Integration saved.');
            onsaved?.(res);
        } catch (e) {
            if (e?.status === 422 && e.errors) {
                const mapped = {};
                for (const [k, v] of Object.entries(e.errors)) mapped[k] = Array.isArray(v) ? v[0] : v;
                errors = mapped;
            } else {
                toast.error(e?.message ?? 'Something went wrong. Please try again.');
            }
        } finally {
            processing = false;
        }
    }
</script>

{#if !chosenDriver}
    <!-- Add flow, step 1: choose a provider/driver. -->
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <span class="text-sm font-semibold text-mono">Choose a provider</span>
            <span class="text-xs text-muted-foreground">Pick how you want to connect {type?.name}.</span>
        </div>

        {#if drivers.length === 0}
            <div class="py-6 text-center"><Spinner /></div>
        {:else}
            <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                {#each drivers as d (d.code)}
                    <button
                        type="button"
                        class="flex cursor-pointer items-center gap-3 px-4 py-3 text-start transition-colors hover:bg-muted"
                        onclick={() => pickDriver(d)}
                    >
                        <span class="flex size-9 items-center justify-center rounded-lg bg-muted text-primary">
                            <i class="ki-filled {d.icon}"></i>
                        </span>
                        <span class="min-w-0 grow truncate text-sm font-medium text-mono">{d.name}</span>
                        <i class="ki-filled ki-right text-muted-foreground"></i>
                    </button>
                {/each}
            </div>
        {/if}

        <div class="flex justify-start border-t border-border pt-4">
            <Button variant="secondary" onclick={() => oncancel?.()}><i class="ki-filled ki-black-left"></i>Back</Button>
        </div>
    </div>
{:else}
    <!-- Step 2 (or edit): the credential fields for the chosen driver. -->
    <form class="flex w-full flex-col gap-5" onsubmit={save}>
        <div class="flex items-center gap-2 text-sm font-medium text-mono">
            <i class="ki-filled {chosenDriver?.icon} text-primary"></i>{chosenDriver?.name}
        </div>

        <Field label="Name" error={errors.name} required hint="A label to recognise this integration by.">
            <Input bind:value={name} invalid={!!errors.name} />
        </Field>

        {#each schema as field (field.key)}
            <SchemaField {field} bind:value={values[field.key]} error={errors[`settings.${field.key}`]} secretSet={!!secretSet[field.key]} />
        {/each}

        <div class="flex items-center justify-between gap-3 border-t border-border pt-4">
            <Button variant="secondary" onclick={back}><i class="ki-filled ki-black-left"></i>Back</Button>
            <Button variant="primary" type="submit" loading={processing}>Save</Button>
        </div>
    </form>
{/if}
