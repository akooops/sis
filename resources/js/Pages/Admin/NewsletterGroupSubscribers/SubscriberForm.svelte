<script>
    /**
     * Subscriber create/edit. No Translations tab: an address and a name are the
     * person's own, not copy the site renders.
     *
     * No `subscribed_at` field either — the server stamps it on create and keeps
     * the original on edit, so it is history rather than something to fill in.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { subscriber = null, groupId = null, onsaved, oncancel } = $props();

    const editing = $derived(!!subscriber);

    const form = useForm({
        // Seeded from the page filter so a drill-down does not re-ask.
        newsletter_group_id: subscriber?.newsletter_group_id ?? groupId ?? null,
        name: subscriber?.name ?? '',
        email: subscriber?.email ?? '',
        is_active: subscriber?.is_active ?? true,
    });

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.newsletter-group-subscribers.update', subscriber.id)
            : route('api.v1.admin.newsletter-group-subscribers.store');
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
        <!-- Remote select: loads page one and narrows by search. -->
        <Field
            label="Group"
            error={form.errors.newsletter_group_id}
            required
            hint="The mailing list this address is on. The same address may be on several."
        >
            <Select
                resource="api.v1.admin.newsletter-groups.index"
                bind:value={form.data.newsletter_group_id}
                labelKey="name"
                placeholder="Search groups…"
                invalid={!!form.errors.newsletter_group_id}
                initialOptions={subscriber?.group ? [{ value: subscriber.group.id, label: subscriber.group.name }] : []}
            />
        </Field>
        <Field label="Email" error={form.errors.email} required hint="Unique within the group — the same address may join another list.">
            <Input type="email" bind:value={form.data.email} invalid={!!form.errors.email} />
        </Field>
    </div>

    <Field label="Name" error={form.errors.name} hint="Optional — a public signup may leave it blank.">
        <Input bind:value={form.data.name} invalid={!!form.errors.name} />
    </Field>

    <!-- Unsubscribing flips this rather than deleting the row, so the next import
         does not silently add the address back. -->
    <Field label="Active" error={form.errors.is_active} hint="Inactive addresses stay on the list but are skipped when a newsletter goes out.">
        <Switch bind:value={form.data.is_active} />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
