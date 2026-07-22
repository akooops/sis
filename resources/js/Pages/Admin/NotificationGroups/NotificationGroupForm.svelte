<script>
    /**
     * Notification group create/edit. A group binds a set of notification types to
     * a set of member users (the routing config). On edit we fetch the full group
     * (the list row omits members) to prefill the multiselects.
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
    const form = useForm({ name: '', code: '', description: '', notification_type_ids: [], user_ids: [] });

    let types = $state([]);
    let memberOptions = $state([]);

    const typeOptions = $derived(types.map((t) => ({ value: t.id, label: t.name })));

    $effect(() => {
        api.get(route('api.v1.admin.notification-types.index'))
            .then((d) => { types = d ?? []; })
            .catch(() => {});

        if (group?.id) {
            api.get(route('api.v1.admin.notification-groups.show', group.id))
                .then((full) => {
                    Object.assign(form.data, {
                        name: full.name ?? '',
                        code: full.code ?? '',
                        description: full.description ?? '',
                        notification_type_ids: full.type_ids ?? [],
                        user_ids: full.user_ids ?? [],
                    });
                    memberOptions = full.members ?? [];
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

<form class="flex w-full max-w-2xl flex-col gap-5" onsubmit={submit}>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Name" error={form.errors.name} required>
            <Input bind:value={form.data.name} invalid={!!form.errors.name} />
        </Field>
        <Field label="Code" error={form.errors.code} required hint="Lowercase, e.g. marketing.blasts.">
            <Input bind:value={form.data.code} invalid={!!form.errors.code} />
        </Field>
    </div>

    <Field label="Description" error={form.errors.description}>
        <textarea class="kt-textarea {form.errors.description ? 'border-destructive' : ''}" rows="2" bind:value={form.data.description}></textarea>
    </Field>

    <Field label="Notification types" error={form.errors.notification_type_ids} hint="Which types this group delivers to its members.">
        <Select options={typeOptions} bind:value={form.data.notification_type_ids} multiple placeholder="Select types" />
    </Field>

    <Field label="Members" error={form.errors.user_ids} hint="Users who receive this group's notifications.">
        <Select
            resource="api.v1.admin.users.index"
            bind:value={form.data.user_ids}
            multiple
            labelKey="username"
            initialOptions={memberOptions}
            placeholder="Search users"
        />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
