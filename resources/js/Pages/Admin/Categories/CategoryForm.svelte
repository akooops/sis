<script>
    /** Category create/edit — a flat record, so no tabs and no translations. */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';
    import { CATEGORY_TYPE_OPTIONS } from '@/lib/category';

    let { category = null, onsaved, oncancel } = $props();

    const editing = $derived(!!category);

    const form = useForm({
        name: category?.name ?? '',
        code: category?.code ?? '',
        type: category?.type ?? 'articles',
    });

    async function submit(e) {
        e.preventDefault();
        const url = editing
            ? route('api.v1.admin.categories.update', category.id)
            : route('api.v1.admin.categories.store');
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
        <Field label="Code" error={form.errors.code} required hint="Lowercase, dash-separated. Unique within its type.">
            <Input bind:value={form.data.code} invalid={!!form.errors.code} />
        </Field>
    </div>

    <Field
        label="Type"
        error={form.errors.type}
        required
        hint={editing
            ? 'Cannot be changed once anything is filed under this category.'
            : 'What this category classifies. It only appears in that type’s picker.'}
    >
        <Select options={CATEGORY_TYPE_OPTIONS} bind:value={form.data.type} clearable={false} />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
