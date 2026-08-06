<script>
    /**
     * Settings — the seeded catalogue, edited one value at a time.
     *
     * There is no Add and no Delete: rows arrive from config/settings.php through
     * SettingsSeeder and `value` is the only writable column, so this is a
     * registry with an edit form — the same shape as Translations, not a CRUD
     * module. The value column carries the whole point of the table, so it
     * renders by type rather than dumping JSON: a choice shows its label, a
     * reference the record's name, and an unset setting says so out loud.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import SettingForm from './SettingForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import {
        SETTING_TYPE_OPTIONS,
        isSettingUnset,
        settingDisplay,
        settingMissingFlags,
        settingModelConfig,
        settingTypeLabel,
    } from '@/lib/setting';

    // No `sort`: the server defaults to group then order, which is the catalogue's
    // own reading order and the only one that groups the sections together.
    const list = useIndex('api.v1.admin.settings.index', { perPage: 25 });

    let groups = $state([]);
    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    $effect(() => {
        api.get(route('api.v1.admin.settings.groups'))
            .then((d) => { groups = d ?? []; })
            .catch(() => {});
    });

    // How many values a list setting shows before it collapses: a setting holding
    // thirty featured articles must not wrap into a wall that owns the row.
    const VALUE_PREVIEW = 3;

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'group', label: 'Group', sortable: true, width: '130px', truncate: false },
        { key: 'key', label: 'Key', sortable: true, truncate: false, maxWidth: '220px' },
        { key: 'name', label: 'Name', sortable: true, truncate: false, maxWidth: '240px' },
        // Not sortable: `type` is absent from the controller's allowedSorts, so
        // the query builder would 400 on the header click.
        { key: 'type', label: 'Type', truncate: false, width: '140px' },
        { key: 'value', label: 'Value', truncate: false, maxWidth: '360px' },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'group', label: 'Group' },
        { value: 'order', label: 'Order' },
        { value: 'key', label: 'Key' },
        { value: 'name', label: 'Name' },
        { value: 'id', label: 'ID' },
        { value: 'created_at', label: 'Created' },
    ];

    const filterConfig = $derived([
        { key: 'group', type: 'select', label: 'Group', options: groups.map((g) => ({ value: g, label: g })) },
        { key: 'type', type: 'select', label: 'Type', options: SETTING_TYPE_OPTIONS },
    ]);

    // An empty table means two very different things, and the seeder one is the
    // answer to "why is there nothing here at all".
    const narrowed = $derived(list.activeFilters > 0 || !!list.search);
    const emptyTitle = $derived(narrowed ? 'No settings found' : 'No settings yet');
    const emptyBody = $derived(
        narrowed
            ? 'No settings match your criteria.'
            : 'Settings are seeded from config/settings.php — an empty table means the seeder has not run.',
    );

    const viewFields = $derived.by(() => {
        if (!viewing) return [];

        const model = settingModelConfig(viewing);
        const out = [
            { label: 'Group', value: viewing.group },
            { label: 'Key', value: viewing.key },
            { label: 'Type', value: settingTypeLabel(viewing) },
        ];

        if (model) out.push({ label: 'Points at', value: model.name });
        if (viewing.type === 'select') {
            out.push({ label: 'Choices', value: (viewing.options ?? []).map((o) => o.label).join(', ') });
        }
        out.push({
            label: viewing.is_multiple ? 'Values' : 'Value',
            value: isSettingUnset(viewing) ? 'Not set' : settingDisplay(viewing).join(', '),
        });

        return out;
    });

    const edit = (s) => { editing = s; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (s) => { viewing = s; viewOpen = true; };
    const showActivity = (s) => { activityRow = s; activityOpen = true; };
</script>

<svelte:head><title>Saud International Schools — Settings</title></svelte:head>

<AdminLayout title="Settings">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <DetailDrawer
        bind:open={viewOpen}
        title="Setting"
        id={viewing?.id}
        heading={viewing?.name}
        subheading={viewing?.description}
        badge={viewing ? { label: settingTypeLabel(viewing), variant: 'secondary' } : null}
        fields={viewFields}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="setting" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

<!-- Search and filters only: the module has no store route, so there is no Add. -->
{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search settings…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    {#if editing}
        <SettingForm setting={editing} onsaved={saved} oncancel={closeForm} />
    {/if}
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
        emptyIcon="ki-filled ki-setting-2"
        {emptyTitle}
        {emptyBody}
        {cells}
        {rowActions}
    />
{/snippet}

<!-- One stored entry. Only a date needs the shared formatter; everything else has
     already been turned into a display string by settingDisplay(). -->
{#snippet entry(row, part)}
    {#if row.type === 'date'}
        <DateTime value={part} format="date" />
    {:else}
        {part}
    {/if}
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'group'}
        <Badge variant="secondary">{row.group}</Badge>
    {:else if column.key === 'key'}
        <span class="font-mono text-2sm text-mono">{row.key}</span>
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="220px" title={row.name} />
        </span>
    {:else if column.key === 'type'}
        <Badge variant="secondary">{settingTypeLabel(row)}</Badge>
    {:else if column.key === 'value'}
        {@const parts = settingDisplay(row)}
        {#if parts.length === 0}
            <span class="text-2sm text-muted-foreground italic">Not set</span>
        {:else if row.is_multiple || row.type === 'model' || row.type === 'select'}
            <!-- A reference whose record is gone keeps its place in the list and
                 says so in destructive: the resolver's tombstone reads like a
                 name otherwise, and "nothing chosen" and "points at a deleted
                 record" ask opposite things of the admin. -->
            {@const missing = settingMissingFlags(row)}
            <div class="flex flex-wrap items-center gap-1" title={parts.join(', ')}>
                {#each parts.slice(0, VALUE_PREVIEW) as part, index}
                    <Badge variant={missing[index] ? 'destructive' : 'secondary'}>{@render entry(row, part)}</Badge>
                {/each}
                {#if parts.length > VALUE_PREVIEW}
                    <Badge variant="primary">+{parts.length - VALUE_PREVIEW} more</Badge>
                {/if}
            </div>
        {:else}
            <!-- A text setting legitimately holds a paragraph (meta description,
                 say), so it is clamped like every other long cell — an unclamped
                 one wraps and gives that row the height of three. -->
            <div class="text-sm text-mono">
                <ClampText lines={2} maxWidth="340px" title={String(parts[0])}>
                    {#snippet children()}{@render entry(row, parts[0])}{/snippet}
                </ClampText>
            </div>
        {/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

<!-- No Delete: a seeded row has nowhere to go, and there is no destroy route. -->
{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('settings.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
    ].filter(Boolean)} />
{/snippet}
