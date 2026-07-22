<script>
    /**
     * Compose form — send a notification of a chosen type. Recipients are not
     * picked here: they are resolved server-side from the groups that route the
     * type, so the form shows a live "will reach N users" preview instead.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { onsaved, oncancel } = $props();

    const form = useForm({ type: '', title: '', body: '', route_name: '', route_params: '' });

    let types = $state([]);
    let preview = $state(null);
    let previewLoading = $state(false);

    const typeOptions = $derived(types.map((t) => ({ value: t.code, label: t.name })));

    $effect(() => {
        api.get(route('api.v1.admin.notification-types.index'))
            .then((data) => { types = data ?? []; })
            .catch(() => {});
    });

    async function onTypeChange(value) {
        form.data.type = value;
        preview = null;
        if (!value) return;
        previewLoading = true;
        try {
            const data = await api.get(route('api.v1.admin.notifications.preview'), { type: value });
            preview = data?.recipients_count ?? 0;
        } catch {
            preview = null;
        } finally {
            previewLoading = false;
        }
    }

    async function submit(event) {
        event.preventDefault();

        // route_params is entered as JSON — parse it before sending.
        let params = null;
        if (form.data.route_params?.trim()) {
            try {
                params = JSON.parse(form.data.route_params);
            } catch {
                form.setError('route_params', 'Must be valid JSON, e.g. {"user":"123"}.');
                return;
            }
        }

        try {
            const res = await form.submit('post', route('api.v1.admin.notifications.store'), {
                transform: (d) => ({
                    type: d.type,
                    title: d.title,
                    body: d.body || null,
                    route_name: d.route_name || null,
                    route_params: params,
                }),
            });
            if (res) {
                toast.success(`Sent to ${res.recipients_count ?? 0} user(s).`);
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full max-w-2xl flex-col gap-5" onsubmit={submit}>
    <Field label="Type" error={form.errors.type} required>
        <Select
            options={typeOptions}
            value={form.data.type}
            onchange={onTypeChange}
            placeholder="Choose a notification type"
            invalid={!!form.errors.type}
        />
        {#if form.data.type}
            <span class="text-xs text-muted-foreground">
                {#if previewLoading}Checking recipients…{:else if preview !== null}Will reach <span class="font-semibold text-mono">{preview}</span> user(s){/if}
            </span>
        {/if}
    </Field>

    <Field label="Title" error={form.errors.title} required>
        <Input bind:value={form.data.title} invalid={!!form.errors.title} />
    </Field>

    <Field label="Message" error={form.errors.body} hint="Shown in the in-app drawer and used as the email/SMS body.">
        <textarea class="kt-textarea {form.errors.body ? 'border-destructive' : ''}" rows="3" bind:value={form.data.body}></textarea>
    </Field>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Route name" error={form.errors.route_name} hint="Optional Ziggy route for the click-through link.">
            <Input bind:value={form.data.route_name} invalid={!!form.errors.route_name} placeholder="web.admin.users.index" />
        </Field>
        <Field label="Route params" error={form.errors.route_params} hint="Optional JSON.">
            <Input bind:value={form.data.route_params} invalid={!!form.errors.route_params} placeholder={'{"user":"123"}'} />
        </Field>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Send notification</Button>
    </div>
</form>
