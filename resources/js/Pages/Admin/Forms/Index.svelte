<script>
    /**
     * Forms index — public forms with a publish workflow and per-locale copy.
     *
     * A form owns TEN destinations and every one of them is INLINE on the row:
     * Build, Analytics, Submissions, the four child lists (notified groups,
     * webhooks, blocked countries, blocked addresses), Edit, Activity, Delete.
     * Nothing hides behind an overflow menu — the actions column is simply
     * widened to hold them (DataTable's `actionsWidth`).
     *
     * The four child lists used to share a single "Connections" launcher, which
     * cost every one of them an extra click and a drawer that existed only to
     * point at four other drawers. Each opens directly now.
     *
     * Build and Analytics are their own routes rather than drawers, because both
     * need a form id in the URL to be linkable. Submissions is a route too: it
     * is the shared submissions page with `filter[form_id]` already applied, and
     * it is reachable from both the count cell and the row action.
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
    import BlockedIpsDrawer from './BlockedIpsDrawer.svelte';
    import BlockedCountriesDrawer from './BlockedCountriesDrawer.svelte';
    import NotificationGroupsDrawer from './NotificationGroupsDrawer.svelte';
    import WebhooksDrawer from './WebhooksDrawer.svelte';
    import FormForm from './FormForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { FORM_STATUS_LABELS, FORM_STATUS_VARIANTS, capacityPercent } from '@/lib/form';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { router } from '@inertiajs/svelte';

    // Deployment state from the page shell, not the API: the blocked-addresses
    // drawer says so out loud when no trusted proxy is set, because every IP
    // block is only as true as request()->ip().
    let { trustsProxies = false } = $props();

    const list = useIndex('api.v1.admin.forms.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    let blockedIpsOpen = $state(false);
    let blockedIpsRow = $state(null);
    let blockedCountriesOpen = $state(false);
    let blockedCountriesRow = $state(null);
    let notifyOpen = $state(false);
    let notifyRow = $state(null);
    let webhooksOpen = $state(false);
    let webhooksRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'slug', label: 'Slug', sortable: true, truncate: false },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
        { key: 'submissions_count', label: 'Submissions', sortable: true, truncate: false },
        { key: 'published_at', label: 'Published', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'status', label: 'Status' },
        { value: 'submissions_count', label: 'Submissions' },
        { value: 'published_at', label: 'Published' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(FORM_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'category_id',
            type: 'resource-select',
            label: 'Category',
            resource: 'api.v1.admin.categories.index',
            labelKey: 'name',
        },
        {
            // Where "Open these forms" from a notification group lands. Declared
            // here as well as on the controller so the deep link arrives as a
            // VISIBLE, clearable filter rather than a silent narrowing the admin
            // cannot see or undo.
            key: 'notification_group_id',
            type: 'resource-select',
            label: 'Notifies group',
            resource: 'api.v1.admin.notification-groups.index',
            labelKey: 'name',
            placeholder: 'Any group',
        },
        { key: 'is_system', type: 'boolean', label: 'System form' },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (f) => { editing = f; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (f) => { viewing = f; viewOpen = true; };
    const showActivity = (f) => { activityRow = f; activityOpen = true; };
    const showBlockedIps = (f) => { blockedIpsRow = f; blockedIpsOpen = true; };
    const showBlockedCountries = (f) => { blockedCountriesRow = f; blockedCountriesOpen = true; };
    const showNotified = (f) => { notifyRow = f; notifyOpen = true; };
    const showWebhooks = (f) => { webhooksRow = f; webhooksOpen = true; };

    /**
     * The four child lists, in the order the row offers them. `key` is what the
     * edit form hands back through `onmanage`, so both entry points — the row
     * actions and the form's own buttons — resolve through this one table and
     * can never drift apart.
     */
    const CONNECTIONS = [
        { key: 'notification-groups', icon: 'ki-notification-status', label: 'Notified groups', permission: 'form-notification-groups.index', open: showNotified },
        { key: 'webhooks', icon: 'ki-cloud-change', label: 'Webhooks', permission: 'form-webhooks.index', open: showWebhooks },
        { key: 'blocked-countries', icon: 'ki-geolocation', label: 'Blocked countries', permission: 'form-blocked-countries.index', open: showBlockedCountries },
        { key: 'blocked-ips', icon: 'ki-shield-cross', label: 'Blocked addresses', permission: 'form-blocked-ips.index', open: showBlockedIps },
    ];

    function openConnection(key, f) {
        if (!f) return;

        CONNECTIONS.find((c) => c.key === key)?.open(f);
    }

    const build = (f) => router.visit(route('web.admin.forms.builder', f.id));
    const analytics = (f) => router.visit(route('web.admin.forms.analytics', f.id));

    // The submissions page is the shared index, pre-filtered to this form —
    // reached from the count cell and from the row actions. Guarded because the
    // page arrived after this one: without the route the count is plain text
    // rather than a link to nowhere.
    const hasSubmissionsPage = route().has('web.admin.form-submissions.index');
    const submissions = (f) =>
        router.visit(route('web.admin.form-submissions.index', { 'filter[form_id]': f.id }));

    /**
     * A form that has collected anything CANNOT be deleted, and there is no
     * override: submissions are the record the form exists to collect, and
     * nothing in this app deletes a submission. FormsController::destroy makes
     * that a 422 — so say why here instead of opening a confirm whose only
     * outcome is a refused request.
     */
    async function remove(f) {
        if (f.submissions_count > 0) {
            toast.error(
                `${f.name} has ${f.submissions_count} submission(s) and cannot be deleted. Submissions are the record the form exists to collect, and they are never deleted — set the form to hidden instead.`,
            );

            return;
        }

        if (!(await confirm({
            body: `Delete ${f.name}? Its uploaded images are returned to the media library, not destroyed. This cannot be undone.`,
            confirmLabel: 'Delete',
            variant: 'destructive',
        }))) return;

        try {
            await api.delete(route('api.v1.admin.forms.destroy', f.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Forms</title></svelte:head>

<AdminLayout title="Forms">
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
        title="Form"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: FORM_STATUS_LABELS[viewing.status] ?? viewing.status, variant: FORM_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={[
            { label: 'Slug', value: viewing?.slug },
            { label: 'Public link', value: viewing?.public_url || 'Not published yet' },
            { label: 'Category', value: viewing?.category?.name || '' },
            { label: 'Pages', value: viewing ? String(viewing.pages_count) : '' },
            { label: 'Fields', value: viewing ? String(viewing.fields_count) : '' },
            { label: 'Submissions', value: viewing ? String(viewing.submissions_count) : '' },
            { label: 'Total limit', value: viewing?.is_limited ? String(viewing.submissions_limit) : 'No limit' },
            { label: 'Per-visitor limit', value: viewing?.is_user_limited ? `${viewing.per_user_limit} by ${viewing.per_user_limit_by}` : 'No limit' },
            { label: 'Spam filter', value: viewing?.is_spam_filtered ? 'On' : 'Off' },
            { label: 'Captcha', value: viewing?.is_captcha_enabled ? 'On' : 'Off' },
            { label: 'Published at', value: viewing?.published_at ?? '' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <BlockedIpsDrawer bind:open={blockedIpsOpen} form={blockedIpsRow} {trustsProxies} />
    <BlockedCountriesDrawer bind:open={blockedCountriesOpen} form={blockedCountriesRow} />
    <NotificationGroupsDrawer bind:open={notifyOpen} form={notifyRow} />
    <WebhooksDrawer bind:open={webhooksOpen} form={webhooksRow} />
    <ActivityDrawer bind:open={activityOpen} subjectType="form" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search forms…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('forms.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add form
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

<!-- `ready` comes from IndexCard: false while the fly transition runs, so the
     editor doesn't measure itself inside a transformed box. -->
{#snippet form(ready)}
    <FormForm
        form={editing}
        {ready}
        onsaved={saved}
        oncancel={closeForm}
        onmanage={(key) => openConnection(key, editing)}
    />
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
        emptyTitle="No forms yet"
        emptyBody="Create a form, then build it with the drag-and-drop editor."
        emptyIcon="ki-filled ki-questionnaire-tablet"
        {cells}
        {rowActions}
        actionsWidth="340px"
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex items-center gap-3">
            {#if row.thumbnail_url}
                <img src={row.thumbnail_url} alt="" class="size-8 shrink-0 rounded object-cover" />
            {/if}
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="200px" title={row.name} />
            </span>
            {#if row.is_system}
                <!-- Settings stay editable; the structure does not. -->
                <Badge variant="secondary" size="sm">System</Badge>
            {/if}
        </div>
    {:else if column.key === 'slug'}
        <Badge variant="secondary">
            <ClampText value={row.slug} maxWidth="160px" title={row.slug} />
        </Badge>
    {:else if column.key === 'status'}
        <Badge variant={FORM_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {FORM_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'submissions_count'}
        <div class="flex items-center gap-2">
            {#if hasSubmissionsPage}
                <button
                    type="button"
                    class="kt-btn kt-btn-xs kt-btn-secondary"
                    onclick={(e) => { e.stopPropagation(); submissions(row); }}
                    title="View submissions"
                >
                    {row.submissions_count}{#if row.is_limited && row.submissions_limit}<span class="text-muted-foreground">/{row.submissions_limit}</span>{/if}
                </button>
            {:else}
                <span class="text-sm">
                    {row.submissions_count}{#if row.is_limited && row.submissions_limit}<span class="text-muted-foreground">/{row.submissions_limit}</span>{/if}
                </span>
            {/if}
            {#if capacityPercent(row) === 100}
                <Badge variant="destructive" size="sm">Full</Badge>
            {/if}
        </div>
    {:else if column.key === 'published_at'}
        {#if row.published_at}<DateTime value={row.published_at} />{:else}—{/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

<!--
    All ten inline — see the note at the top. Every entry is permission-gated by
    the caller, so a row shows exactly what this admin may do with it.
-->
{#snippet rowActions(row)}
    <RowActions
        actions={[
            hasPermission('form-fields.index') && { icon: 'ki-element-plus', label: 'Build', onclick: () => build(row) },
            hasPermission('forms.show') && { icon: 'ki-chart-line-up', label: 'Analytics', onclick: () => analytics(row) },
            hasSubmissionsPage && hasPermission('form-submissions.index') && { icon: 'ki-questionnaire-tablet', label: 'Submissions', onclick: () => submissions(row) },
            ...CONNECTIONS.map((c) => hasPermission(c.permission) && { icon: c.icon, label: c.label, onclick: () => c.open(row) }),
            hasPermission('forms.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
            hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
            hasPermission('forms.destroy') && !row.is_system && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
        ].filter(Boolean)}
    />
{/snippet}
