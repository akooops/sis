<script>
    /**
     * Translations — one enabled language at a time, edited in place.
     *
     * The keys are paginated from the database (so search, sorting and filters
     * behave like every other index); each row's value is read out of
     * lang/{code}/{group}.php and written straight back to it. Nothing is stored
     * in a translations column, so what you edit here is exactly what @lang()
     * resolves. There is no create form — the key registry is seeded from
     * config/translations.php, never CRUD.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Select from '@/components/form/Select.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import InlineEdit from '@/components/form/InlineEdit.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api, ApiError } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let languageId = $state(null);
    let language = $state(null);
    let groups = $state([]);

    // immediate:false — there is nothing to fetch until a language is picked.
    // routeParams MUST be the function form: a plain value is read once at init
    // and would pin the table to the first language forever.
    const list = useIndex('api.v1.admin.translations.index', {
        perPage: 25,
        sort: 'group',
        immediate: false,
        routeParams: () => languageId,
    });

    let filtersOpen = $state(false);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const showActivity = (row) => { activityRow = row; activityOpen = true; };

    const columns = [
        { key: 'group', label: 'Group', sortable: true, width: '140px', truncate: false },
        { key: 'key', label: 'Key', sortable: true, truncate: false, maxWidth: '320px' },
        // Not sortable: `value` is not a DB column, so the query builder would 400.
        { key: 'value', label: 'Translation', truncate: false, maxWidth: '480px' },
    ];

    const sortOptions = [
        { value: 'group', label: 'File' },
        { value: 'key', label: 'Key' },
        { value: 'created_at', label: 'Created' },
    ];

    const filterConfig = $derived([
        { key: 'group', type: 'select', label: 'Group', options: groups.map((g) => ({ value: g, label: g })) },
    ]);

    const canEdit = $derived(hasPermission('translations.update'));

    // Preselect the default language so the page opens on something useful, and
    // load the group list for the filter drawer.
    $effect(() => {
        api.get(route('api.v1.admin.translation-key-groups.index'))
            .then((d) => { groups = d ?? []; })
            .catch(() => {});
    });

    function selectLanguage(id, row = null) {
        languageId = id;
        language = row;
        if (!id) {
            return;
        }

        list.reload();
        if (!row) {
            api.get(route('api.v1.admin.languages.show', id))
                .then((d) => { language = d; })
                .catch(() => {});
        }
    }

    /**
     * Patch the row in place rather than refetching: a list.refresh() would
     * rebuild the table under the cursor and lose the user's place mid-edit.
     * The counters do move, so only those are refetched.
     */
    async function save(row, value) {
        try {
            const data = await api.put(route('api.v1.admin.translations.update', [languageId, row.id]), { value });
            row.value = data?.value ?? '';
            row.is_translated = !!data?.is_translated;
        } catch (e) {
            // A 422 is shown on the cell itself by InlineEdit — a toast for a
            // per-field error would just be noise. Anything else is worth one.
            if (!(e instanceof ApiError) || e.status !== 422) {
                toast.error(e?.message ?? 'Something went wrong. Please try again.');
            }
            throw e;
        }
    }
</script>

<svelte:head><title>Saud International Schools — Translations</title></svelte:head>

<AdminLayout title="Translations">
    <IndexCard showForm={false} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <!-- Subject is the key, so this reads its history across every locale. -->
    <ActivityDrawer
        bind:open={activityOpen}
        subjectType="translation_key"
        subjectId={activityRow?.id}
        title={activityRow ? `${activityRow.group}.${activityRow.key}` : null}
    />
</AdminLayout>

<!--
    One full-width column, so the card header's justify-between has nothing to
    push apart and the two rows stack: pick the language first, then narrow it.
-->
{#snippet toolbar()}
    <div class="flex w-full flex-col gap-3 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium whitespace-nowrap text-mono">Language</span>
            <div class="w-56">
                <Select
                    resource="api.v1.admin.languages.index"
                    resourceParams={{ filter: { is_enabled: 1 } }}
                    value={languageId}
                    clearable={false}
                    initialOptions={language ? [{ value: language.id, label: language.name }] : []}
                    placeholder="Select a language"
                    onchange={(v) => selectLanguage(v)}
                />
            </div>
        </div>

        {#if languageId}
            <div class="flex flex-wrap items-center gap-2 border-t border-border pt-3">
                <SearchBar placeholder="Search keys…" value={list.search} onsearch={(v) => list.setSearch(v)} />
                <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
            </div>
        {/if}
    </div>
{/snippet}

<!-- No create form: the key registry is seeded, never CRUD. -->
{#snippet form()}{/snippet}

{#snippet table()}
    {#if !languageId}
        <EmptyState
            icon="ki-filled ki-flag"
            title="Pick a language"
            body="Choose an enabled language above to see and edit its translations."
        />
    {:else}
        <DataTable
            {columns}
            rows={list.rows}
            loading={list.loading}
            meta={list.meta}
            sort={list.params.sort}
            onSort={list.toggleSort}
            onPageChange={list.goToPage}
            onPerPageChange={list.setPerPage}
            emptyTitle="No translation keys"
            emptyBody="No keys match your criteria."
            {cells}
            {rowActions}
        />
    {/if}
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'group'}
        <Badge variant="secondary">{row.group}</Badge>
    {:else if column.key === 'key'}
        <span class="font-mono text-2sm text-mono">{row.key}</span>
    {:else if column.key === 'value'}
        <InlineEdit
            value={row.value}
            placeholder=""
            disabled={!canEdit}
            onsave={(v) => save(row, v)}
        />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
    ].filter(Boolean)} />
{/snippet}
