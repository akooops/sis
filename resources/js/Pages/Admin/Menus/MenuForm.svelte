<script>
    /**
     * Menu create/edit. No Translations tab: a menu is a name and a machine key —
     * the copy the public site renders lives on its items.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SlugInput from '@/components/form/SlugInput.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { menu = null, onsaved, oncancel } = $props();

    const editing = $derived(!!menu);

    const form = useForm({
        name: menu?.name ?? '',
        code: menu?.code ?? '',
    });

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.menus.update', menu.id) : route('api.v1.admin.menus.store');
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
        <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
            <Input bind:value={form.data.name} invalid={!!form.errors.name} />
        </Field>
        <Field
            label="Code"
            error={form.errors.code}
            required
            hint={menu?.is_system
                ? 'This menu ships with the app — the site looks it up by this code, so it is fixed.'
                : 'Filled in from the name until you edit it. The public site resolves the menu by this.'}
        >
            <SlugInput bind:value={form.data.code} source={form.data.name} disabled={!!menu?.is_system} invalid={!!form.errors.code} />
        </Field>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
