<script>
    /**
     * Form submissions — everything the public forms have collected.
     *
     * ITS OWN PAGE, not a drawer hanging off Forms. The Forms table drills in
     * with `?filter[form_id]=…` and useIndex reads that out of the URL at init,
     * so the deep link and the filter drawer are the same state — a drawer would
     * have made the filtered list unlinkable and unexportable.
     *
     * ENTIRELY READ-ONLY. Nothing here creates, edits or deletes a submission:
     * an answer an admin could rewrite stops being evidence of what was sent,
     * and a submission is the record the form exists to collect. Hence no form
     * snippet and no delete action — the only ways out are the CSV export and
     * the retention prune.
     *
     * The TABLE carries submission metadata only. Answers live in the detail
     * drawer, which renders them from the submission's own `fields` snapshot —
     * the table has no business showing answer data it would have to re-derive
     * from the live form.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import ServerExportButton from '@/components/data/ServerExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import SubmissionDrawer from './SubmissionDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        DEVICE_TYPE_OPTIONS,
        SUBMISSION_STATUS_LABELS,
        SUBMISSION_STATUS_VARIANTS,
    } from '@/lib/form';
    import { hasPermission } from '@/lib/permissions';

    const list = useIndex('api.v1.admin.form-submissions.index', { perPage: 15, sort: '-created_at' });

    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);

    const formId = $derived(list.params.filter?.form_id ?? null);

    // Deep link (/admin/form-submissions?filter[form_id]=<id>): the loaded rows
    // already carry their form, so the filter drawer can show its NAME straight
    // away. Select resolves the label itself when no row matched — a form with
    // no submissions has nothing here to read it from.
    const filteredForm = $derived(
        formId ? (list.rows.find((r) => r.form_id === formId)?.form ?? null) : null,
    );

    /*
     * METADATA ONLY — no answer columns. The Form column is dropped once one
     * form is filtered, because it would repeat the same name down the whole
     * table. Answers are the drawer's job.
     */
    const columns = $derived([
        // The ULID is the reference — there is no second identifier column.
        { key: 'id', label: 'ID', sortable: true, width: '120px', truncate: false },
        ...(formId ? [] : [{ key: 'form', label: 'Form', truncate: false }]),
        { key: 'status', label: 'Status', sortable: true, truncate: false, width: '140px' },
        { key: 'submitted_at', label: 'Submitted', sortable: true, truncate: false },
        { key: 'country_code', label: 'Country', truncate: false, width: '100px' },
        { key: 'device_type', label: 'Device', truncate: false, width: '110px' },
        { key: 'spam_score', label: 'Spam', sortable: true, truncate: false, width: '90px' },
    ]);

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'submitted_at', label: 'Submitted' },
        { value: 'status', label: 'Status' },
        { value: 'spam_score', label: 'Spam score' },
        { value: 'id', label: 'ID' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'form_id',
            type: 'resource-select',
            label: 'Form',
            resource: 'api.v1.admin.forms.index',
            placeholder: 'All forms',
            initialOptions: () => (filteredForm ? [{ value: filteredForm.id, label: filteredForm.name }] : []),
        },
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(SUBMISSION_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            // The column stores the two-letter code, so the picker selects on
            // `code` rather than on the country's id.
            key: 'country_code',
            type: 'resource-select',
            label: 'Country',
            resource: 'api.v1.admin.countries.index',
            valueKey: 'code',
            placeholder: 'All countries',
        },
        { key: 'device_type', type: 'select', label: 'Device', options: DEVICE_TYPE_OPTIONS },
        { key: 'is_honeypot_triggered', type: 'boolean', label: 'Honeypot triggered' },
        { key: 'submitted', type: 'daterange', label: 'Submitted' },
    ];

    /*
     * The export goes to the SERVER with the filters and sort the table is
     * showing, minus page/per_page — a CSV of page 2 of 40 is not an export.
     * The endpoint re-applies the same filters, so the file can never hold rows
     * the list was not showing.
     */
    const exportParams = $derived({
        filter: { ...(list.params.filter ?? {}) },
        sort: list.params.sort || undefined,
    });

    const canExport = $derived(hasPermission('form-submissions.export'));

    const view = (row) => {
        viewing = row;
        viewOpen = true;
    };
</script>

<svelte:head><title>Saud International Schools — Form Submissions</title></svelte:head>

<AdminLayout title="Form Submissions">
    <IndexCard showForm={false} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <SubmissionDrawer bind:open={viewOpen} submission={viewing} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search by ID…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
    </div>
    {#if canExport}
        <!-- The endpoint exports ONE form: its columns are that form's current
             fields, and a CSV has exactly one header row. -->
        <ServerExportButton
            routeName="api.v1.admin.form-submissions.export"
            routeParams={formId}
            params={exportParams}
            fallbackFilename="submissions.csv"
            label="Export CSV"
            disabled={!formId}
            title={formId
                ? 'Download every submission matching these filters. Columns follow the form’s current fields.'
                : 'Filter to a single form first — the columns are that form’s fields.'}
        />
    {/if}
{/snippet}

<!-- Read-only module: nothing creates, edits or deletes a submission. -->
{#snippet form()}{/snippet}

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
        emptyTitle="No submissions yet"
        emptyBody="Once a published form is filled in, every answer lands here."
        emptyIcon="ki-filled ki-questionnaire-tablet"
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <div class="flex items-center gap-2">
            <IdBadge id={row.id} onclick={() => view(row)} />
            {#if row.is_honeypot_triggered}
                <Badge variant="destructive" size="sm">Bot</Badge>
            {/if}
        </div>
    {:else if column.key === 'form'}
        <span class="text-sm text-mono">
            <ClampText value={row.form_name ?? ''} maxWidth="200px" title={row.form_name} />
        </span>
    {:else if column.key === 'status'}
        <Badge variant={SUBMISSION_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {SUBMISSION_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'submitted_at'}
        {#if row.submitted_at}<DateTime value={row.submitted_at} />{:else}—{/if}
    {:else if column.key === 'country_code'}
        {#if row.country_code}
            <Badge variant="secondary">{row.country_code}</Badge>
        {:else}
            <span class="text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'device_type'}
        {row.device_type ?? '—'}
    {:else if column.key === 'spam_score'}
        <Badge variant={row.spam_score >= 60 ? 'destructive' : row.spam_score >= 30 ? 'warning' : 'secondary'}>
            {row.spam_score}
        </Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

<!-- View only: a submission is never edited and never deleted. -->
{#snippet rowActions(row)}
    <RowActions actions={[
        hasPermission('form-submissions.show') && { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
    ].filter(Boolean)} />
{/snippet}
