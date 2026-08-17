<script>
    /**
     * Rename a talent pool.
     *
     * NO CREATE. Pools are DISCOVERED, not declared: a nightly k-means over
     * candidate embeddings produces them and an LLM names each result. A
     * hand-made pool would have no centroid, so it could neither gather members
     * nor place a posting — which is why there is no `clusters.store` permission
     * and no store route.
     *
     * "KEEP THIS NAME" DEFAULTS TO CHECKED, and that is the whole point of the
     * form. RebuildClusters renames every unlocked pool on its next run, so a
     * rename without the lock is undone by 03:00. The server stays dumb and
     * applies whatever it is sent; the decision lives here, where the person
     * making it can see it.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { cluster = null, ready = true, onsaved, oncancel } = $props();

    const form = useForm({
        name: cluster?.name ?? '',
        description: cluster?.description ?? '',
        // Checked by default on a pool the rebuild still owns: someone typing a
        // name almost certainly wants to keep it.
        is_locked: cluster?.is_locked ?? true,
    });

    async function submit(event) {
        event.preventDefault();

        if (!cluster?.id) return;

        try {
            const res = await form.put(route('api.v1.admin.clusters.update', cluster.id));
            if (res) {
                toast.success('Updated successfully.');
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <Field label="Name" error={form.errors.name} required hint="What HR calls this pool. A rebuild names an unnamed one “Unnamed pool”.">
        <Input bind:value={form.data.name} invalid={!!form.errors.name} />
    </Field>

    <Field label="Description" error={form.errors.description} hint="Optional. Nothing writes this automatically.">
        <Input bind:value={form.data.description} invalid={!!form.errors.description} />
    </Field>

    <Field label="Keep this name" error={form.errors.is_locked}>
        <div class="flex items-center gap-3">
            <Switch bind:value={form.data.is_locked} />
            <span class="text-sm text-muted-foreground">
                The nightly rebuild renames unlocked pools. Leave this on to keep the name you type.
            </span>
        </div>
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
