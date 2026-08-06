<script>
    /** API key create/edit form panel. Create returns a one-time token via onsaved(res). */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { apiKey = null, onsaved, oncancel } = $props();

    const editing = $derived(!!apiKey);
    const form = useForm({ name: '', allowed_ips: '', expires_at: '' });
    if (apiKey) {
        Object.assign(form.data, {
            name: apiKey.name ?? '',
            allowed_ips: (apiKey.allowed_ips ?? []).join('\n'),
            expires_at: apiKey.expires_at ?? '',
        });
    }

    function payload(data) {
        const ips = String(data.allowed_ips || '').split(/[\n,]+/).map((s) => s.trim()).filter(Boolean);
        const out = { name: data.name, allowed_ips: ips.length ? ips : null };
        if (data.expires_at) out.expires_at = data.expires_at;
        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.api-keys.update', apiKey.id) : route('api.v1.admin.api-keys.store');
        const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
        if (res) {
            toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
            onsaved?.(res);
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <Field label="Name" error={form.errors.name} required>
        <Input bind:value={form.data.name} invalid={!!form.errors.name} />
    </Field>
    <Field label="Allowed IPs" error={form.errors.allowed_ips} hint="One IP per line. Leave blank to allow all.">
        <textarea class="kt-input min-h-[90px]" bind:value={form.data.allowed_ips}></textarea>
    </Field>
    <Field label="Expires at" error={form.errors.expires_at}>
        <DatePicker bind:value={form.data.expires_at} />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
