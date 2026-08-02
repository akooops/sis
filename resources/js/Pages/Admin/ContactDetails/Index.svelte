<script>
    /**
     * Contact details index — the phones, emails, addresses and social links the
     * public site lists, in a hand-set order.
     *
     * The type catalogue is config, not a table: this page reads it once from
     * `contact-types` and everything type-dependent — the badge, the filter, and
     * how the value cell renders — derives from that one map. Nothing here
     * restates the codes, so adding a type to the config adds it to the UI.
     *
     * The form and the reorder drawer take the catalogue as a prop rather than
     * fetching it again — it is a five-row config file, and one read per page
     * visit is enough (the same call IntegrationsDrawer makes with its drivers).
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import ContactDetailForm from './ContactDetailForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.contact-details.index', { perPage: 15, sort: 'order' });

    let types = $state([]);
    // Rides along in the one type request: the form picks from it, and this page
    // reads a social row's icon and label off it rather than off the row.
    let platforms = $state([]);
    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    $effect(() => {
        api.get(route('api.v1.admin.contact-types.index'))
            .then((res) => {
                types = [...(res?.types ?? [])].sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0));
                platforms = res?.platforms ?? [];
            })
            .catch(() => {});
    });

    const typeMap = $derived(new Map(types.map((t) => [t.code, t])));

    const typeName = (code) => typeMap.get(code)?.name ?? code ?? '';
    const typeIcon = (code) => typeMap.get(code)?.icon ?? null;

    const platformMap = $derived(new Map(platforms.map((p) => [p.code, p])));

    // A row stores the network code; the name and the mark both come off the
    // registry, so renaming X back to Twitter is one line of config.
    const platformName = (code) => platformMap.get(code)?.name ?? code ?? '';
    const platformIcon = (code) => platformMap.get(code)?.icon ?? null;

    /**
     * The SHAPE the type declares for `value` — 'phone' | 'email' | 'url', null
     * for a type that carries no scalar, undefined while the registry is loading.
     * The cells switch on this rather than on the code, so a sixth phone-shaped
     * type renders as a tel: link without touching this file.
     */
    const valueKind = (code) => (typeMap.has(code) ? (typeMap.get(code).value ?? null) : undefined);

    /**
     * One string out of a translated map. English wins when it is filled — this
     * UI is hardcoded English (`<html lang="en">`) — and anything else is only a
     * fallback so a record translated so far into Arabic still shows its address
     * instead of a blank cell. Picking whichever locale happens to sit first in
     * the stored JSON would put Arabic in an ltr column on a seeded row.
     */
    function translated(value) {
        if (!value) return '';
        if (typeof value === 'string') return value;

        return value.en || (Object.values(value).find((v) => v) ?? '');
    }

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'type', label: 'Type', sortable: true, width: '150px', truncate: false },
        { key: 'value', label: 'Value', truncate: false, maxWidth: '320px' },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'type', label: 'Type' },
        { value: 'created_at', label: 'Created' },
    ];

    const filterConfig = $derived([
        {
            key: 'type',
            type: 'select',
            label: 'Type',
            options: types.map((t) => ({ value: t.code, label: t.name })),
        },
    ]);

    const VALUE_LABELS = { phone: 'Phone number', email: 'Email address', url: 'Link' };

    // Only the rows this record actually fills: a phone has no postal address, and
    // a drawer that lists every column of every type is mostly blanks.
    const viewFields = $derived.by(() => {
        if (!viewing) return [];

        const kind = valueKind(viewing.type);
        const address = translated(viewing.address);
        const out = [{ label: 'Title', value: translated(viewing.title) }];

        if (viewing.value) out.push({ label: VALUE_LABELS[kind] ?? 'Value', value: viewing.value });
        if (viewing.platform) out.push({ label: 'Platform', value: platformName(viewing.platform) });
        if (address) out.push({ label: 'Address', value: address });
        if (viewing.map_url) out.push({ label: 'Map', value: viewing.map_url });
        if (viewing.latitude != null && viewing.longitude != null) {
            out.push({ label: 'Coordinates', value: `${viewing.latitude}, ${viewing.longitude}` });
        }
        out.push({ label: 'Position', value: String((viewing.order ?? 0) + 1) });

        return out;
    });

    const create = () => { editing = null; showForm = true; };
    const edit = (d) => { editing = d; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (d) => { viewing = d; viewOpen = true; };
    const showActivity = (d) => { activityRow = d; activityOpen = true; };

    async function remove(d) {
        if (!(await confirm({
            body: `Delete ${d.name}? The remaining contact details keep their order.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.contact-details.destroy', d.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Contact details</title></svelte:head>

<AdminLayout title="Contact details">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} {types} onsaved={() => list.refresh()} />
    <DetailDrawer
        bind:open={viewOpen}
        title="Contact detail"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: typeName(viewing.type), variant: 'secondary' } : null}
        fields={viewFields}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="contact_detail" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search contact details…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('contact-details.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('contact-details.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add contact detail
                </button>
            {/if}
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <ContactDetailForm detail={editing} {types} {platforms} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable
        {columns}
        rows={list.rows}
        loading={list.loading}
        meta={list.meta}
        sort={list.params.sort}
        onSort={list.toggleSort}
        onPageChange={list.goToPage}
        onPerPageChange={list.setPerPage}
        onRowClick={view}
        emptyTitle="No contact details yet"
        emptyBody="Add a phone, email or address for the public site."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="240px" title={row.name} />
        </span>
    {:else if column.key === 'type'}
        <Badge variant="secondary">
            {#if typeIcon(row.type)}<i class="ki-filled {typeIcon(row.type)} me-1"></i>{/if}
            {typeName(row.type)}
        </Badge>
    {:else if column.key === 'value'}
        <!-- Every anchor stops propagation, or the row click opens the view drawer
             behind the dialler/mail client the admin just asked for. -->
        {#if valueKind(row.type) === 'phone'}
            <a
                href="tel:{row.value}"
                class="kt-link inline-flex items-center gap-1.5 text-sm"
                onclick={(e) => e.stopPropagation()}
                dir="ltr"
            >
                <ClampText value={row.value ?? ''} maxWidth="220px" title={row.value} />
            </a>
        {:else if valueKind(row.type) === 'email'}
            <a
                href="mailto:{row.value}"
                class="kt-link inline-flex items-center gap-1.5 text-sm"
                onclick={(e) => e.stopPropagation()}
            >
                <ClampText value={row.value ?? ''} maxWidth="240px" title={row.value} />
            </a>
        {:else if valueKind(row.type) === 'url'}
            <a
                href={row.value}
                target="_blank"
                rel="noreferrer noopener"
                class="kt-link inline-flex items-center gap-1.5 text-sm"
                onclick={(e) => e.stopPropagation()}
                title={row.value}
            >
                {#if platformIcon(row.platform)}<i class="ki-filled {platformIcon(row.platform)} shrink-0"></i>{/if}
                <ClampText value={platformName(row.platform) || row.value || 'Link'} maxWidth="200px" />
            </a>
        {:else if valueKind(row.type) === null}
            {@const address = translated(row.address)}
            <span class="text-sm text-secondary-foreground">
                <ClampText value={address} lines={2} maxWidth="280px" title={address} />
            </span>
        {:else}
            <span class="text-sm">{row.value ?? ''}</span>
        {/if}
    {:else if column.key === 'order'}
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('contact-details.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('contact-details.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
