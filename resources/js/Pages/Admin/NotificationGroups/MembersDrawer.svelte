<script>
    /**
     * A group's members and each member's delivery integrations. Members are set
     * on the group form; here you pick, per member, which email/SMS integrations
     * their notifications from this group ship through (in-app is always on).
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Select from '@/components/form/Select.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), group = null, integrationOptions = [] } = $props();

    let members = $state([]);
    let loading = $state(false);
    let editingId = $state(null);
    let draftIds = $state([]);
    let saving = $state(false);
    let loadedKey = $state(null);

    async function load() {
        loading = true;
        try {
            const data = await api.get(route('api.v1.admin.notification-groups.members.index', group.id), { per_page: 100, sort: '-created_at' });
            members = data?.data ?? [];
        } catch {
            members = [];
        } finally {
            loading = false;
        }
    }

    // Load once per open, and reset so reopening the drawer refetches.
    $effect(() => {
        if (open && group?.id && loadedKey !== group.id) {
            loadedKey = group.id;
            editingId = null;
            load();
        }
        if (!open) loadedKey = null;
    });

    function startEdit(m) {
        editingId = m.id;
        draftIds = [...(m.integration_ids ?? [])];
    }

    function cancelEdit() {
        editingId = null;
    }

    async function saveEdit(m) {
        saving = true;
        try {
            const updated = await api.put(route('api.v1.admin.group-users.integrations', m.id), { integration_ids: draftIds });
            const idx = members.findIndex((x) => x.id === m.id);
            if (idx >= 0) members[idx] = updated;
            editingId = null;
            toast.success('Integrations updated.');
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }

    function memberName(m) {
        const u = m.user;
        if (!u) return m.user_id;
        const full = `${u.firstname ?? ''} ${u.lastname ?? ''}`.trim();
        return full || u.username || u.email;
    }
</script>

<Drawer bind:open title={group ? `Members — ${group.name}` : 'Members'} width="w-[460px]">
    {#if loading && members.length === 0}
        <div class="flex flex-col gap-2">
            {#each Array(4) as _}<div class="h-16 rounded-lg bg-muted animate-pulse"></div>{/each}
        </div>
    {:else if members.length === 0}
        <EmptyState icon="ki-filled ki-people" title="No members" body="Add members from the group form." />
    {:else}
        <div class="flex flex-col gap-3">
            {#each members as m (m.id)}
                <div class="rounded-lg border border-border p-3">
                    <div class="flex items-center gap-3">
                        <Avatar src={m.user?.avatar_url} name={memberName(m)} size="sm" />
                        <div class="min-w-0 grow">
                            <div class="truncate text-sm font-medium text-mono">{memberName(m)}</div>
                            <div class="truncate text-xs text-muted-foreground">{m.user?.email ?? ''}</div>
                        </div>
                        {#if editingId !== m.id}
                            <button class="kt-btn kt-btn-xs kt-btn-light" onclick={() => startEdit(m)}>
                                <i class="ki-filled ki-pencil"></i>Integrations
                            </button>
                        {/if}
                    </div>

                    {#if editingId === m.id}
                        <div class="mt-3 flex flex-col gap-2">
                            <Select options={integrationOptions} bind:value={draftIds} multiple placeholder="Select email/SMS integrations" />
                            <div class="flex justify-end gap-2">
                                <button class="kt-btn kt-btn-xs kt-btn-secondary" onclick={cancelEdit} disabled={saving}>Cancel</button>
                                <button class="kt-btn kt-btn-xs kt-btn-primary" onclick={() => saveEdit(m)} disabled={saving}>Save</button>
                            </div>
                        </div>
                    {:else if m.integrations?.length}
                        <div class="mt-2 flex flex-wrap gap-1">
                            {#each m.integrations as i}
                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">{i.name}</span>
                            {/each}
                        </div>
                    {:else}
                        <div class="mt-2 text-xs text-muted-foreground">In-app only</div>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}
</Drawer>
