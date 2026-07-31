<script>
    /**
     * Users → Sessions drawer: where this account is signed in, and a revoke per
     * device — built like ActivityDrawer, because it answers the same shape of
     * question. A toolbar box on top (search, live/expired, the count, and the
     * log-out-everywhere button), then a timeline of devices, each expanding to a
     * details table rather than dumping a user-agent string into the list.
     *
     * Not a PivotDrawer: nothing here can be attached. A session is created by
     * signing in, never by an admin, so there is no select and no form. "Log out
     * all devices" lives in the row menu's sessions group, not here — one way to
     * do a thing beats two.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { formatDateTime, formatRelative } from '@/lib/date';
    import { untrack } from 'svelte';

    let { open = $bindable(false), user = null } = $props();

    // routeParams is a function: the drawer is reused across rows, and a plain
    // value would pin every fetch to the first user it was opened for.
    // readUrl:false so it can't fight the page's query string; immediate:false so
    // a mounted-but-closed drawer doesn't fetch.
    const list = useIndex('api.v1.admin.sessions.index', {
        perPage: 10,
        sort: '-last_activity',
        readUrl: false,
        immediate: false,
        routeParams: () => user?.id,
    });

    let expanded = $state(new Set());
    let showExpired = $state(false);

    const userName = $derived(user ? `${user.firstname ?? ''} ${user.lastname ?? ''}`.trim() : '');

    function toggle(id) {
        const next = new Set(expanded);
        next.has(id) ? next.delete(id) : next.add(id);
        expanded = next;
    }

    // Reopening for a different user must drop the previous one's search, page
    // and expanded rows, or they silently apply to this one.
    $effect(() => {
        if (!open || !user?.id) return;
        user.id;
        untrack(() => {
            expanded = new Set();
            showExpired = false;
            list.apply({ filter: {} });
        });
    });

    async function revoke(row) {
        const body = row.is_current
            ? 'This is the device you are using. Revoking it signs you out immediately.'
            : 'Revoke this session? The device using it will be signed out.';

        if (!(await confirm({ title: 'Revoke session', body, variant: 'destructive' }))) return;

        try {
            await api.delete(route('api.v1.admin.sessions.destroy', row.id));
            toast.success('Session revoked.');
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    /** The row's details table — everything the one-line summary leaves out. */
    function details(row) {
        return [
            { label: 'IP address', value: row.ip_address ?? '' },
            { label: 'Device', value: row.user_agent ?? 'Unknown device' },
            { label: 'Last active', value: formatDateTime(row.last_activity) },
            { label: 'Signed in', value: formatDateTime(row.created_at) },
            { label: 'Status', value: row.is_expired ? 'Expired' : 'Active' },
        ];
    }
</script>

<Drawer bind:open title="Sessions" width="w-[620px]">
    {#snippet header()}
        <div class="flex min-w-0 flex-col gap-0.5">
            <h3 class="text-base font-semibold text-mono">Sessions</h3>
            {#if userName}
                <div class="text-xs font-normal text-muted-foreground">
                    <ClampText value={userName} title={userName} />
                </div>
            {/if}
        </div>
    {/snippet}

    <div class="flex flex-col gap-4">
        <!-- toolbar -->
        <div class="flex flex-col gap-3 rounded-lg border border-border bg-muted/30 p-3">
            <div class="grid grid-cols-2 gap-3">
                <Field label="Search">
                    <SearchBar placeholder="IP or device…" value={list.search} onsearch={(v) => list.setSearch(v)} />
                </Field>
                <Field label="Show">
                    <select
                        class="kt-select"
                        value={showExpired ? '1' : '0'}
                        onchange={(e) => {
                            showExpired = e.currentTarget.value === '1';
                            list.setFilters({ ...list.params.filter, expired: showExpired ? 1 : null });
                        }}
                    >
                        <option value="0">Active sessions</option>
                        <option value="1">Expired sessions</option>
                    </select>
                </Field>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span class="text-xs text-muted-foreground">
                    {#if list.meta?.total}
                        Showing {list.meta.from}–{list.meta.to} of {list.meta.total}
                    {/if}
                </span>
            </div>
        </div>

        <!-- devices -->
        {#if list.loading}
            <div class="flex flex-col gap-5">
                {#each Array(3) as _, i (i)}
                    <div class="flex gap-3">
                        <Skeleton class="size-8 shrink-0 rounded-full" />
                        <div class="flex grow flex-col gap-2">
                            <Skeleton class="h-3.5 w-2/3 rounded" />
                            <Skeleton class="h-3 w-1/3 rounded" />
                        </div>
                    </div>
                {/each}
            </div>
        {:else if !list.rows.length}
            <EmptyState
                icon="ki-filled ki-technology-4"
                title={showExpired ? 'No expired sessions' : 'Not signed in anywhere'}
                body={showExpired
                    ? 'Expired sessions are pruned daily.'
                    : 'This account has no active sessions right now.'}
            />
        {:else}
            <ul class="flex flex-col">
                {#each list.rows as row (row.id)}
                    <li class="relative flex gap-3 pb-5 last:pb-0">
                        <!-- connector -->
                        <span class="absolute start-4 top-9 bottom-0 w-px bg-border last:hidden"></span>

                        <span
                            class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-background text-sm text-muted-foreground"
                        >
                            <i class="ki-filled ki-technology-4"></i>
                        </span>

                        <div class="flex min-w-0 grow flex-col gap-1.5">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-mono">{row.ip_address ?? ''}</span>
                                {#if row.is_current}
                                    <Badge variant="success" size="sm">This device</Badge>
                                {/if}
                                {#if row.is_expired}
                                    <Badge variant="secondary" size="sm">Expired</Badge>
                                {/if}
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span title={row.last_activity}>Last active {formatRelative(row.last_activity)}</span>
                                <span class="opacity-60">·</span>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-primary hover:underline"
                                    onclick={() => toggle(row.id)}
                                >
                                    <i class={expanded.has(row.id) ? 'ki-filled ki-up' : 'ki-filled ki-down'}></i>
                                    {expanded.has(row.id) ? 'Collapse' : 'Details'}
                                </button>
                            </div>

                            {#if expanded.has(row.id)}
                                <div class="pt-1">
                                    <div class="overflow-x-auto rounded-lg border border-border">
                                        <table class="w-full text-xs">
                                            <tbody>
                                                {#each details(row) as field (field.label)}
                                                    <tr class="border-b border-border align-top last:border-0">
                                                        <td class="w-[110px] px-3 py-2 font-medium text-muted-foreground">
                                                            {field.label}
                                                        </td>
                                                        <td class="max-w-[320px] px-3 py-2 text-mono">
                                                            <ClampText value={field.value} lines={3} toggle copy />
                                                        </td>
                                                    </tr>
                                                {/each}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            {/if}
                        </div>

                        <button
                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost h-8 shrink-0 text-destructive"
                            onclick={() => revoke(row)}
                            aria-label="Revoke"
                            title="Revoke this session"
                        >
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </li>
                {/each}
            </ul>

            {#if list.meta?.total > list.meta?.per_page}
                <Pagination meta={list.meta} onPageChange={(p) => list.goToPage(p)} />
            {/if}
        {/if}
    </div>
</Drawer>
