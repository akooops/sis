<script>
    /**
     * Integration create/edit form for one already-chosen driver. Fields are
     * driven by the driver's schema. No driver picker and no test here — the
     * drawer owns driver selection (a prior step) and this is a plain fly form
     * with a Back. Secrets stay write-only (a stored secret is never sent back).
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SchemaField from '@/components/form/SchemaField.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { type, driver, integration = null, onsaved, onback } = $props();

    const editing = !!integration;
    const schema = driver?.schema ?? [];
    const secretSet = integration?.secrets_set ?? {};

    let name = $state(integration?.name ?? '');
    let values = $state(seed());
    let errors = $state({});
    let processing = $state(false);

    function seed() {
        const next = {};
        for (const f of schema) {
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
                    driver: driver.code,
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

<form class="flex w-full flex-col gap-5" onsubmit={save}>
    <div class="flex items-center gap-2 text-sm font-medium text-mono">
        <i class="ki-filled {driver?.icon} text-primary"></i>{driver?.name}
    </div>

    <Field label="Name" error={errors.name} required hint="A label to recognise this integration by.">
        <Input bind:value={name} invalid={!!errors.name} />
    </Field>

    {#each schema as field (field.key)}
        <SchemaField {field} bind:value={values[field.key]} error={errors[`settings.${field.key}`]} secretSet={!!secretSet[field.key]} />
    {/each}

    <div class="flex items-center justify-between gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={onback}><i class="ki-filled ki-black-left"></i>Back</Button>
        <Button variant="primary" type="submit" loading={processing}>Save</Button>
    </div>
</form>
