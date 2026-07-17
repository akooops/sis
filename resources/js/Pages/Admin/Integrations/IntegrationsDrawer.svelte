<script>
    /**
     * Drawer of the integrations configured under one type. A multi-step fly flow:
     * the list, a "choose a driver" step, and the credential form — each flies in
     * with a Back. The list is paginated; drivers and types are not.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import IntegrationForm from './IntegrationForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { hasPermission } from '@/lib/permissions';
    import { untrack } from 'svelte';
    import { fly } from 'svelte/transition';

    let { open = $bindable(false), type = null } = $props();

    // readUrl:false so it can't fight the page's query string; immediate:false so
    // a mounted-but-closed drawer doesn't fetch; filter is set per-type on open.
    const list = useIndex('api.v1.admin.integrations.index', {
        perPage: 10,
        sort: '-created_at',
        readUrl: false,
        immediate: false,
    });

    let drivers = $state([]);
    let view = $state('list'); // list | driver | form
    let editing = $state(null);
    let chosenDriver = $state(null);
    // Plain (non-reactive) so the reset gate below can't clobber a user action.
    let loadedKey = null;

    const driverName = (code) => drivers.find((d) => d.code === code)?.name ?? code;

    // Initialise once per type: reopening for a different type resets the flow;
    // a re-run for the same type must not throw away a step the user opened.
    $effect(() => {
        if (!open || !type?.id) {
            if (!open) loadedKey = null;
            return;
        }
        if (type.id === loadedKey) return;
        loadedKey = type.id;
        untrack(() => {
            view = 'list';
            editing = null;
            chosenDriver = null;
            list.setFilters({ integration_type_id: type.id });
            loadDrivers();
        });
    });

    async function loadDrivers() {
        try {
            drivers = (await api.get(route('api.v1.admin.integration-types.drivers', type.id))) ?? [];
        } catch (e) {
            toast.error(e?.message ?? 'Could not load drivers.');
        }
    }

    function startAdd() {
        editing = null;
        chosenDriver = null;
        view = 'driver';
    }
    function pickDriver(d) {
        chosenDriver = d;
        view = 'form';
    }
    function startEdit(row) {
        editing = row;
        chosenDriver = drivers.find((d) => d.code === row.driver) ?? { code: row.driver, name: row.driver, icon: '', schema: [] };
        view = 'form';
    }
    function backFromForm() {
        if (!editing) {
            view = 'driver';
        } else {
            view = 'list';
            editing = null;
            chosenDriver = null;
        }
    }
    function saved() {
        view = 'list';
        editing = null;
        chosenDriver = null;
        list.refresh();
    }

    async function toggle(row) {
        try {
            await api.patch(route('api.v1.admin.integrations.toggle', row.id));
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(row) {
        if (!(await confirm({ title: 'Delete integration?', body: `"${row.name}" will be removed. This can't be undone.`, variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.integrations.destroy', row.id));
            toast.success('Integration deleted.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open>
    {#snippet header()}
        <div class="flex min-w-0 flex-col">
            <span class="truncate text-sm font-semibold text-mono">{type?.name} integrations</span>
            <span class="text-xs font-normal text-muted-foreground">{list.meta?.total ?? 0} configured</span>
        </div>
    {/snippet}

    {#if view === 'form'}
        <div class="flex flex-col gap-5" in:fly={{ x: '100%', duration: 350 }}>
            <IntegrationForm {type} driver={chosenDriver} integration={editing} onsaved={saved} onback={backFromForm} />
        </div>
    {:else if view === 'driver'}
        <div class="flex flex-col gap-4" in:fly={{ x: '100%', duration: 350 }}>
            <div class="flex flex-col gap-1">
                <span class="text-sm font-semibold text-mono">Choose a provider</span>
                <span class="text-xs text-muted-foreground">Pick how you want to connect {type?.name}.</span>
            </div>
            {#if drivers.length === 0}
                <div class="py-6 text-center"><Spinner /></div>
            {:else}
                <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each drivers as d (d.code)}
                        <button type="button" class="flex cursor-pointer items-center gap-3 px-4 py-3 text-start transition-colors hover:bg-muted" onclick={() => pickDriver(d)}>
                            <span class="flex size-9 items-center justify-center rounded-lg bg-muted text-primary"><i class="ki-filled {d.icon}"></i></span>
                            <span class="min-w-0 grow truncate text-sm font-medium text-mono">{d.name}</span>
                            <i class="ki-filled ki-right text-muted-foreground"></i>
                        </button>
                    {/each}
                </div>
            {/if}
            <div class="flex justify-start border-t border-border pt-4">
                <Button variant="secondary" onclick={() => (view = 'list')}><i class="ki-filled ki-black-left"></i>Back</Button>
            </div>
        </div>
    {:else}
        <div class="flex flex-col gap-4" in:fly={{ x: '-100%', duration: 350 }}>
            {#if hasPermission('integrations.store')}
                <div class="flex justify-end">
                    <Button variant="primary" onclick={startAdd}><i class="ki-filled ki-plus"></i>Add integration</Button>
                </div>
            {/if}

            {#if list.loading}
                <div class="py-6 text-center"><Spinner /></div>
            {:else if list.rows.length === 0}
                <EmptyState icon="ki-filled ki-technology-4" title="No integrations yet" body="Add one to start using this service." />
            {:else}
                <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each list.rows as row (row.id)}
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                            <button type="button" class="flex min-w-0 grow cursor-pointer flex-col text-start" onclick={() => startEdit(row)}>
                                <span class="truncate text-sm font-medium text-mono">{row.name}</span>
                                <span class="truncate text-xs text-muted-foreground">{driverName(row.driver)}</span>
                            </button>
                            <div class="flex shrink-0 items-center gap-2">
                                {#if hasPermission('integrations.update')}
                                    <Switch value={row.is_enabled} onchange={() => toggle(row)} />
                                {/if}
                                <Dropdown>
                                    {#snippet trigger()}
                                        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
                                    {/snippet}
                                    {#if hasPermission('integrations.update')}
                                        <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => startEdit(row)}><span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">Edit</span></button></div>
                                    {/if}
                                    {#if hasPermission('integrations.destroy')}
                                        <div class="kt-menu-separator"></div>
                                        <div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}><span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Delete</span></button></div>
                                    {/if}
                                </Dropdown>
                            </div>
                        </div>
                    {/each}
                </div>

                {#if list.meta?.total > list.meta?.per_page}
                    <Pagination meta={list.meta} onPageChange={(p) => list.goToPage(p)} />
                {/if}
            {/if}
        </div>
    {/if}
</Drawer>
