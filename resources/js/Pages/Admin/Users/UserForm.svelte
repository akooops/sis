<script>
    /** User create/edit form panel (rendered in the index card fly-swap). */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import PasswordInput from '@/components/form/PasswordInput.svelte';
    import PhoneInput from '@/components/form/PhoneInput.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';
    import { t } from '@/lib/i18n';

    let { user = null, onsaved, oncancel } = $props();

    const editing = $derived(!!user);
    const form = useForm({ firstname: '', lastname: '', username: '', email: '', password: '', phone: '', avatar: null });

    // Seeded fresh each time the panel mounts.
    if (user) {
        Object.assign(form.data, {
            firstname: user.firstname ?? '',
            lastname: user.lastname ?? '',
            username: user.username ?? '',
            email: user.email ?? '',
            phone: user.phone ?? '',
        });
    }

    function payload(data) {
        const out = { ...data };
        if (!out.password) delete out.password;
        if (!out.avatar) delete out.avatar;
        if (!out.phone) delete out.phone;
        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.users.update', user.id) : route('api.v1.admin.users.store');
        const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
        if (res) {
            toast.success($t(editing ? 'common.feedback.updated' : 'common.feedback.created'));
            onsaved?.();
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <Field label={$t('users.fields.avatar')}>
        <MediaPicker type="images" bind:value={form.data.avatar} previewUrl={user?.avatar_url} />
    </Field>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label={$t('users.fields.firstname')} error={form.errors.firstname} required>
            <Input bind:value={form.data.firstname} invalid={!!form.errors.firstname} />
        </Field>
        <Field label={$t('users.fields.lastname')} error={form.errors.lastname} required>
            <Input bind:value={form.data.lastname} invalid={!!form.errors.lastname} />
        </Field>
    </div>
    <Field label={$t('users.fields.username')} error={form.errors.username} required>
        <Input bind:value={form.data.username} invalid={!!form.errors.username} />
    </Field>
    <Field label={$t('users.fields.email')} error={form.errors.email} required>
        <Input type="email" bind:value={form.data.email} invalid={!!form.errors.email} />
    </Field>
    <Field label={$t('users.fields.phone')} error={form.errors.phone}>
        <PhoneInput bind:value={form.data.phone} invalid={!!form.errors.phone} />
    </Field>
    <Field label={$t('users.fields.password')} error={form.errors.password} hint={editing ? 'Leave blank to keep current' : null} required={!editing}>
        <PasswordInput autocomplete="new-password" bind:value={form.data.password} invalid={!!form.errors.password} />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>{$t('common.actions.cancel')}</Button>
        <Button variant="primary" type="submit" loading={form.processing}>{$t('common.actions.save')}</Button>
    </div>
</form>
