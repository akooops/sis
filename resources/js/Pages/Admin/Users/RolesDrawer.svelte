<script>
    /** Users → Roles pivot drawer: assign/detach roles for a user. */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    let { open = $bindable(false), user = null } = $props();

    let rows = $state([]);
    let loading = $state(false);
    let selected = $state([]);
    let saving = $state(false);

    async function load() {
        if (!user) return;
        loading = true;
        try {
            const data = await api.get(route('api.v1.admin.user-roles.index', user.id), { include: 'role', per_page: 100 });
            rows = data?.data ?? [];
        } finally {
            loading = false;
        }
    }

    $effect(() => {
        if (open && user) {
            selected = [];
            load();
        }
    });

    async function assign() {
        if (!selected.length) return;
        saving = true;
        try {
            await api.post(route('api.v1.admin.user-roles.store', user.id), { roles: selected });
            toast.success('Updated successfully.');
            selected = [];
            await load();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }

    async function detach(pivot) {
        if (!(await confirm({ title: 'Delete confirmation', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.user-roles.destroy', pivot.id));
            toast.success('Deleted successfully.');
            await load();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open title={`Roles for ${user ? `${user.firstname} ${user.lastname}` : ''}`}>
    <div class="flex flex-col gap-5">
        <Field label="Assign roles">
            <div class="flex items-end gap-2">
                <div class="grow">
                    <Select resource="api.v1.admin.roles.index" labelKey="name" valueKey="id" multiple bind:value={selected} />
                </div>
                <Button variant="primary" onclick={assign} loading={saving} disabled={!selected.length}>Add</Button>
            </div>
        </Field>

        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">Assigned roles</span>
            {#if loading}
                <div class="py-4 text-center"><Spinner /></div>
            {:else if rows.length === 0}
                <p class="text-sm text-muted-foreground">No roles assigned yet.</p>
            {:else}
                <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each rows as row (row.id)}
                        <div class="flex items-center justify-between px-3 py-2">
                            <Badge variant="primary">{row.role?.name ?? row.role_id}</Badge>
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
