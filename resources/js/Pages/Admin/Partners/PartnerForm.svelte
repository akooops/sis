<script>
    /**
     * Partner create/edit. No `order` field by design — position is set by
     * dragging in the Reorder drawer, and a new partner goes on the end.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { partner = null, onsaved, oncancel } = $props();

    const editing = $derived(!!partner);

    const form = useForm({
        name: partner?.name ?? '',
        url: partner?.url ?? '',
        logo: null,
    });

    function payload(data) {
        const out = { ...data };
        if (!out.logo) delete out.logo;

        return out;
    }

    async function submit(e) {
        e.preventDefault();
        const url = editing
            ? route('api.v1.admin.partners.update', partner.id)
            : route('api.v1.admin.partners.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
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
    <Field label="Logo" error={form.errors.logo} required={!editing}>
        <MediaPicker accept={['images']} bind:value={form.data.logo} previewUrl={partner?.logo_url} />
    </Field>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Name" error={form.errors.name} required>
            <Input bind:value={form.data.name} invalid={!!form.errors.name} />
        </Field>
        <Field label="Website" error={form.errors.url} required hint="Where the logo links to.">
            <Input bind:value={form.data.url} invalid={!!form.errors.url} placeholder="https://…" />
        </Field>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
