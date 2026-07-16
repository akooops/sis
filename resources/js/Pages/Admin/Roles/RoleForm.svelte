<script>
    /** Role create/edit form panel. */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { role = null, onsaved, oncancel } = $props();

    const editing = $derived(!!role);
    const form = useForm({ name: '', is_default: false });
    if (role) Object.assign(form.data, { name: role.name ?? '', is_default: !!role.is_default });

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.roles.update', role.id) : route('api.v1.admin.roles.store');
        const res = await form.submit(editing ? 'put' : 'post', url);
        if (res) {
            toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
            onsaved?.();
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <Field label="Name" error={form.errors.name} required>
        <Input bind:value={form.data.name} invalid={!!form.errors.name} />
    </Field>
    <label class="kt-label">
        <input type="checkbox" class="kt-checkbox" bind:checked={form.data.is_default} />
        <span class="kt-checkbox-label">Default</span>
    </label>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
