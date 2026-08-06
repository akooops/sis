<script>
    /**
     * Blocked addresses for ONE form — add, list, remove.
     *
     * Not a PivotDrawer: there is no second record to select. The row IS the
     * value the admin typed, so the drawer carries its own two-field add row
     * (address + why) rather than a resource picker, and there is no edit —
     * changing the address is a different block, not this one amended.
     *
     * The server canonicalises what is typed (" 10.0.0.5 " → 10.0.0.5,
     * 10.0.0.5/24 → 10.0.0.0/24) and derives whether it is a range, so the value
     * shown back after saving may not be the exact string that was sent. That is
     * the point: one range, one row, one spelling.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { untrack } from 'svelte';

    let { open = $bindable(false), form = null, trustsProxies = false } = $props();

    // pollMs:0 + readUrl:false + immediate:false — a drawer stays mounted while
    // closed, so a poll here would fetch a list nobody is looking at, and reading
    // the page's query string would drag the Forms table's filters in.
    const list = useIndex('api.v1.admin.form-blocked-ips.index', {
        perPage: 10,
        sort: '-created_at',
        readUrl: false,
        immediate: false,
        pollMs: 0,
    });

    const entry = useForm({ form_id: null, value: '', note: '' });

    // Plain last-key compare, not a reactive guard: this effect resets the add
    // row, and re-running it on any unrelated change would wipe half-typed input.
    let lastKey = null;

    $effect(() => {
        if (!open || !form?.id) {
            lastKey = null;
            return;
        }

        const key = form.id;
        if (key === lastKey) return;
        lastKey = key;

        untrack(() => {
            entry.reset();
            // useIndex reads its filter once at init, so scoping to this form has
            // to happen on open — otherwise the drawer shows the first form's list
            // forever.
            list.setFilters({ form_id: key });
        });
    });

    /** The /nn of a typed range, or null when it is a single address. */
    function prefixBits(value) {
        const text = String(value ?? '');
        const slash = text.indexOf('/');
        if (slash === -1) return null;

        const bits = Number(text.slice(slash + 1).trim());

        return Number.isInteger(bits) ? bits : null;
    }

    async function add(event) {
        event.preventDefault();

        if (!form?.id) return;

        // /8 and wider is millions of addresses, and /0 is the entire internet.
        // Server-side it is a perfectly valid range, which is exactly why it is
        // worth saying out loud before it lands on a live form.
        const bits = prefixBits(entry.data.value);

        if (bits !== null && bits <= 8) {
            const confirmed = await confirm({
                body: bits === 0
                    ? `A /0 range blocks every address there is — nobody could submit ${form.name} again. Add it anyway?`
                    : `A /${bits} range covers millions of addresses. Add it to ${form.name} anyway?`,
                confirmLabel: 'Add it',
                variant: 'destructive',
            });

            if (!confirmed) return;
        }

        entry.data.form_id = form.id;

        try {
            const saved = await entry.post(route('api.v1.admin.form-blocked-ips.store'));
            if (!saved) return; // 422 — errors are on the fields

            toast.success(`${saved.value} is now blocked.`);
            entry.reset();
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(row) {
        if (!(await confirm({
            body: `Unblock ${row.value}? Submissions from ${row.is_cidr ? 'that range' : 'that address'} will be accepted again.`,
            confirmLabel: 'Unblock',
            variant: 'destructive',
        }))) return;

        try {
            await api.delete(route('api.v1.admin.form-blocked-ips.destroy', row.id));
            toast.success('Unblocked successfully.');
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    const columns = [
        { key: 'value', label: 'Address', sortable: true, truncate: false },
        { key: 'note', label: 'Note', truncate: false },
        { key: 'created_at', label: 'Added', sortable: true, truncate: false, width: '150px' },
    ];
</script>

<Drawer bind:open title={`Blocked addresses — ${form?.name ?? ''}`} width="w-[620px]">
    <div class="flex flex-col gap-4">
        {#if !trustsProxies}
            <!-- The block is enforced against request()->ip(). With no trusted
                 proxy configured, that is whatever actually opened the socket. -->
            <Alert variant="warning">
                <div class="flex flex-col gap-1">
                    <span class="font-medium">No trusted proxy is configured.</span>
                    <span>
                        Blocks are matched against the address the request arrives from. If this site sits behind a load
                        balancer or CDN, that is the proxy's address, not the visitor's — blocking it would block everyone,
                        and blocking a real visitor's address would block no one. Set <code>TRUSTED_PROXIES</code> before
                        relying on this list.
                    </span>
                </div>
            </Alert>
        {/if}

        {#if hasPermission('form-blocked-ips.store')}
            <form class="flex flex-col gap-3" onsubmit={add}>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="min-w-0 sm:w-[240px]">
                        <Field
                            label="Address or range"
                            error={entry.errors.value}
                            hint="A single IPv4/IPv6 address, or CIDR (203.0.113.0/24)."
                            required
                        >
                            <Input bind:value={entry.data.value} placeholder="203.0.113.5" invalid={!!entry.errors.value} />
                        </Field>
                    </div>
                    <div class="min-w-0 grow">
                        <Field label="Note" error={entry.errors.note} hint="Why it is blocked — for whoever reads this next.">
                            <Input bind:value={entry.data.note} placeholder="Repeated spam" invalid={!!entry.errors.note} />
                        </Field>
                    </div>
                    <div class="sm:pt-[26px]">
                        <Button type="submit" variant="primary" loading={entry.processing} disabled={!entry.data.value?.trim()}>
                            <i class="ki-filled ki-plus"></i>Block
                        </Button>
                    </div>
                </div>
                {#if entry.errors.form_id}
                    <span class="text-xs text-destructive">{entry.errors.form_id}</span>
                {/if}
            </form>
        {/if}

        <SearchBar placeholder="Search addresses…" value={list.search} onsearch={(v) => list.setSearch(v)} />

        <DataTable
            {columns}
            rows={list.rows}
            loading={list.loading}
            meta={list.meta}
            sort={list.params.sort}
            onSort={list.toggleSort}
            onPageChange={list.goToPage}
            onPerPageChange={list.setPerPage}
            emptyTitle="Nothing blocked"
            emptyBody="Add an address or a CIDR range above to refuse submissions from it."
            emptyIcon="ki-filled ki-shield-cross"
            {cells}
            rowActions={hasPermission('form-blocked-ips.destroy') ? rowActions : undefined}
        />
    </div>
</Drawer>

{#snippet cells(row, column)}
    {#if column.key === 'value'}
        <div class="flex items-center gap-2">
            <span class="font-mono text-sm text-mono">
                <ClampText value={row.value} maxWidth="220px" title={row.value} />
            </span>
            {#if row.is_cidr}
                <Badge variant="secondary" size="sm">Range</Badge>
            {/if}
        </div>
    {:else if column.key === 'note'}
        {#if row.note}
            <ClampText value={row.note} maxWidth="220px" title={row.note} />
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <div class="inline-flex items-center">
        <button
            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive"
            onclick={() => remove(row)}
            aria-label="Unblock"
            title="Unblock"
        >
            <i class="ki-filled ki-trash"></i>
        </button>
    </div>
{/snippet}
