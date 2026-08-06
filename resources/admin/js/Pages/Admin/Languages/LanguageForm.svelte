<script>
    /**
     * Language create/edit. The lang/{code}/ folder follows this form: creating a
     * language creates it, changing the code renames it. Deleting never removes
     * it — the translations are source and survive the row.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { language = null, onsaved, oncancel } = $props();

    const editing = $derived(!!language);

    const form = useForm({
        name: language?.name ?? '',
        code: language?.code ?? '',
        is_rtl: language?.is_rtl ?? false,
        is_enabled: language?.is_enabled ?? false,
        is_default: language?.is_default ?? false,
        flag: null,
    });

    function payload(data) {
        const out = { ...data };
        if (!out.flag) delete out.flag;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.languages.update', language.id)
            : route('api.v1.admin.languages.store');
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
    <Field label="Flag" hint="The bundled flag for the code is used when empty.">
        <MediaPicker accept={['images']} bind:value={form.data.flag} previewUrl={language?.flag_url} />
    </Field>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Name" error={form.errors.name} required>
            <Input bind:value={form.data.name} invalid={!!form.errors.name} />
        </Field>
        <Field label="Code" error={form.errors.code} required hint="ISO 639-1, e.g. en or pt_BR. Changing it renames the lang folder.">
            <Input bind:value={form.data.code} invalid={!!form.errors.code} />
        </Field>
    </div>

    <Field label="Right-to-left" error={form.errors.is_rtl}>
        <Switch bind:value={form.data.is_rtl} />
    </Field>

    <Field label="Enabled" error={form.errors.is_enabled} hint="Only enabled languages appear in the Translations picker.">
        <Switch bind:value={form.data.is_enabled} />
    </Field>

    <Field label="Default" error={form.errors.is_default} hint="Only one language is default, setting this clears it on the others and enables this one.">
        <Switch bind:value={form.data.is_default} />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
