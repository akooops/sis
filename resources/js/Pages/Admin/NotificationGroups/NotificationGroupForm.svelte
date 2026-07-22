<script>
    /**
     * Notification group create/edit. A group binds a set of notification types
     * to one optional email integration (none = in-app only). Members are NOT
     * managed here — use the Members drawer (a pivot resource). On edit we fetch
     * the full group (the list row omits type_ids) to prefill the selects.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { group = null, onsaved, oncancel } = $props();

    const editing = $derived(!!group);
    const form = useForm({ name: '', code: '', integration_id: null, notification_type_ids: [] });

    let types = $state([]);
    let emailTypeId = $state(null);

    // The list row already carries the integration's name — seed the select's
    // label synchronously so editing never flashes the raw id. (Select also
    // self-resolves unknown values via filter[id] as a fallback.)
    const integrationSeed = group?.integration
        ? [{ value: group.integration.id, label: group.integration.name }]
        : [];

    const typeOptions = $derived(types.map((t) => ({ value: t.id, label: t.name })));

    $effect(() => {
        api.get(route('api.v1.admin.notification-types.index'))
            .then((d) => { types = d ?? []; })
            .catch(() => {});

        // The integration picker only offers email integrations — resolve the
        // email type id first, then the Select browses integrations filtered by it.
        api.get(route('api.v1.admin.integration-types.index'))
            .then((d) => { emailTypeId = (d ?? []).find((t) => t.code === 'email')?.id ?? null; })
            .catch(() => {});

        if (group?.id) {
            api.get(route('api.v1.admin.notification-groups.show', group.id))
                .then((full) => {
                    Object.assign(form.data, {
                        name: full.name ?? '',
                        code: full.code ?? '',
                        integration_id: full.integration_id ?? null,
                        notification_type_ids: full.type_ids ?? [],
                    });
                })
                .catch(() => {});
        }
    });

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.notification-groups.update', group.id) : route('api.v1.admin.notification-groups.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url);
            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Name" error={form.errors.name} required>
            <Input bind:value={form.data.name} invalid={!!form.errors.name} />
        </Field>
        <Field label="Code" error={form.errors.code} required hint="Lowercase, e.g. approvals.">
            <Input bind:value={form.data.code} invalid={!!form.errors.code} />
        </Field>
    </div>

    <Field label="Notification types" error={form.errors.notification_type_ids} hint="Which types this group delivers to its members.">
        <Select options={typeOptions} bind:value={form.data.notification_type_ids} multiple placeholder="Select types" />
    </Field>

    <Field label="Email integration" error={form.errors.integration_id} hint="Deliveries also ship through this integration. None = in-app only.">
        <Select
            resource={emailTypeId ? 'api.v1.admin.integrations.index' : null}
            resourceParams={{ filter: { integration_type_id: emailTypeId } }}
            bind:value={form.data.integration_id}
            clearable
            initialOptions={integrationSeed}
            placeholder="None (in-app only)"
        />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
