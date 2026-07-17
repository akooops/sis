<script>
    /** Integrations index — a card grid of integration types; a card opens the
     * per-type drawer. Mirrors the Media card-grid-in-IndexCard shape. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import IntegrationsDrawer from './IntegrationsDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { STATUS_LABELS, STATUS_VARIANTS } from '@/lib/integrations';

    // A small fixed catalogue — not paginated.
    const list = useIndex('api.v1.admin.integration-types.index');

    let drawerOpen = $state(false);
    let selected = $state(null);
    let wasOpen = false;

    function openType(t) {
        selected = t;
        drawerOpen = true;
    }

    // Closing the drawer may have changed integrations — refresh the card counts.
    $effect(() => {
        if (wasOpen && !drawerOpen) list.refresh();
        wasOpen = drawerOpen;
    });
</script>

<svelte:head><title>Saud International Schools — Integrations</title></svelte:head>

<AdminLayout title="Integrations">
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <IntegrationsDrawer bind:open={drawerOpen} type={selected} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex flex-col">
        <span class="text-sm font-semibold text-mono">Integration types</span>
        <span class="text-xs text-muted-foreground">Configure the services this system connects to.</span>
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <div class="flex flex-col gap-4 p-5">
        {#if list.loading && list.rows.length === 0}
            <!-- Skeletons only on the very first load; later refreshes (e.g. after
                 closing the drawer) update the cards in place, no re-flash. -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {#each Array(3) as _}
                    <div class="rounded-lg border border-border p-5">
                        <Skeleton class="mb-3 size-11 rounded-lg" />
                        <Skeleton class="h-4 w-1/2" />
                        <Skeleton class="mt-2 h-3 w-2/3" />
                    </div>
                {/each}
            </div>
        {:else if list.rows.length === 0}
            <EmptyState icon="ki-filled ki-technology-4" title="No integrations" body="No integration types are configured yet." />
        {:else}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {#each list.rows as t (t.id)}
                    <button
                        type="button"
                        class="group flex cursor-pointer flex-col rounded-lg border border-border p-5 text-start transition-colors hover:border-primary/50"
                        onclick={() => openType(t)}
                    >
                        <div class="mb-3 flex items-start justify-between">
                            <span class="flex size-11 items-center justify-center rounded-lg bg-muted text-primary">
                                <i class="ki-filled {t.icon} text-xl"></i>
                            </span>
                            <Badge variant={STATUS_VARIANTS[t.status] ?? 'secondary'}>{STATUS_LABELS[t.status] ?? t.status}</Badge>
                        </div>
                        <span class="text-base font-semibold text-mono">{t.name}</span>
                        <span class="mt-1 text-sm text-muted-foreground">
                            {#if t.integrations_count === 0}
                                No integrations yet
                            {:else}
                                {t.integrations_count} integration{t.integrations_count === 1 ? '' : 's'} · {t.active_count} active
                            {/if}
                        </span>
                    </button>
                {/each}
            </div>
        {/if}
    </div>
{/snippet}
