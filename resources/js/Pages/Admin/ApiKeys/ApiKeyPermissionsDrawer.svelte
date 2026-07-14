<script>
    /** API key → Permissions pivot drawer. */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), apiKey = null } = $props();

    let rows = $state([]);
    let loading = $state(false);
    let selected = $state([]);
    let saving = $state(false);

    async function load() {
        if (!apiKey) return;
        loading = true;
        try {
            const data = await api.get(route('api.v1.admin.api-key-permissions.index', apiKey.id), { include: 'permission', per_page: 200 });
            rows = data?.data ?? [];
        } finally {
            loading = false;
        }
    }

    $effect(() => {
        if (open && apiKey) {
            selected = [];
            load();
        }
    });

    async function assign() {
        if (!selected.length) return;
        saving = true;
        try {
            await api.post(route('api.v1.admin.api-key-permissions.store', apiKey.id), { permissions: selected });
            toast.success($t('common.feedback.updated'));
            selected = [];
            await load();
        } catch (e) {
            toast.error(e?.message ?? $t('common.feedback.error'));
        } finally {
            saving = false;
        }
    }

    async function detach(pivot) {
        if (!(await confirm({ variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.api-key-permissions.destroy', pivot.id));
            toast.success($t('common.feedback.deleted'));
            await load();
        } catch (e) {
            toast.error(e?.message ?? $t('common.feedback.error'));
        }
    }
</script>

<Drawer bind:open title={$t('api_keys.permissions_drawer.title', { name: apiKey?.name ?? '' })}>
    <div class="flex flex-col gap-5">
        <Field label={$t('api_keys.permissions_drawer.assign')}>
            <div class="flex items-end gap-2">
                <div class="grow">
                    <Select resource="api.v1.admin.permissions.index" labelKey="name" valueKey="id" multiple bind:value={selected} />
                </div>
                <Button variant="primary" onclick={assign} loading={saving} disabled={!selected.length}>{$t('common.actions.add')}</Button>
            </div>
        </Field>

        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">{$t('api_keys.permissions_drawer.current')}</span>
            {#if loading}
                <div class="py-4 text-center"><Spinner /></div>
            {:else if rows.length === 0}
                <p class="text-sm text-muted-foreground">{$t('api_keys.permissions_drawer.empty')}</p>
            {:else}
                <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each rows as row (row.id)}
                        <div class="flex items-center justify-between px-3 py-2">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-mono">{row.permission?.name ?? row.permission_id}</span>
                                <span class="text-xs text-muted-foreground">{row.permission?.code ?? ''}</span>
                            </div>
                            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" onclick={() => detach(row)} aria-label="Remove">
                                <i class="ki-filled ki-trash"></i>
                            </button>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>
    </div>
</Drawer>
