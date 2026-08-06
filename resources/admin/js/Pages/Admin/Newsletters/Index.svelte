<script>
    /**
     * Newsletters index — issues archived on the website, emailed to newsletter
     * groups by the scheduler, or both.
     *
     * The two sides are shown side by side and never merged: Channels says which
     * of them apply, each badge taking its colour from ITS OWN status, and the two
     * status columns spell those statuses out. A publish-only issue stays draft on
     * the email side forever, and vice versa.
     *
     * Edit and Delete are offered on every row, sent ones included — an issue that
     * went out is still editable, and re-sending is confirmed in the form.
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
    import NewsletterForm from './NewsletterForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import {
        NEWSLETTER_PUBLISH_STATUS_LABELS,
        NEWSLETTER_PUBLISH_STATUS_VARIANTS,
        NEWSLETTER_SEND_STATUS_LABELS,
        NEWSLETTER_SEND_STATUS_VARIANTS,
    } from '@/lib/newsletter';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.newsletters.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    let emailTypeId = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        // Which sides apply — the two switches, each tinted by its own status.
        { key: 'channels', label: 'Channels', truncate: false },
        { key: 'published_status', label: 'Website status', sortable: true, truncate: false },
        { key: 'sent_status', label: 'Email status', sortable: true, truncate: false },
        { key: 'sent_at', label: 'Sent at', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'subject', label: 'Subject' },
        { value: 'published_status', label: 'Website status' },
        { value: 'published_at', label: 'Publish at' },
        { value: 'sent_status', label: 'Email status' },
        { value: 'sent_at', label: 'Send at' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Every status is offered here,
    // `failed` included — filtering is a view of what exists, not a state the
    // form may set.
    const filterConfig = [
        { key: 'is_published', type: 'boolean', label: 'Published on website' },
        { key: 'is_sendable', type: 'boolean', label: 'Sent by email' },
        {
            key: 'published_status',
            type: 'select',
            label: 'Website status',
            options: Object.entries(NEWSLETTER_PUBLISH_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'sent_status',
            type: 'select',
            label: 'Email status',
            options: Object.entries(NEWSLETTER_SEND_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'newsletter_group_id',
            type: 'resource-select',
            label: 'Group',
            resource: 'api.v1.admin.newsletter-groups.index',
            placeholder: 'All groups',
        },
        {
            key: 'integration_id',
            type: 'resource-select',
            label: 'Integration',
            resource: 'api.v1.admin.integrations.index',
            // Only email integrations can ever be on a newsletter; unfiltered
            // until the type id lands, which is a blink.
            resourceParams: () => (emailTypeId ? { filter: { integration_type_id: emailTypeId } } : {}),
            placeholder: 'All integrations',
        },
    ];

    const dateField = (label, value) => (value ? { label, date: value } : { label, value: '' });

    const create = () => { editing = null; showForm = true; };
    const edit = (n) => { editing = n; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (n) => { viewing = n; viewOpen = true; };
    const showActivity = (n) => { activityRow = n; activityOpen = true; };

    $effect(() => {
        api.get(route('api.v1.admin.integration-types.index'))
            .then((d) => { emailTypeId = (d ?? []).find((t) => t.code === 'email')?.id ?? null; })
            .catch(() => {});
    });

    async function remove(n) {
        if (!(await confirm({
            body: `Delete ${n.name}? Its targeting goes with it and its file returns to the media library. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.newsletters.destroy', n.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Newsletters</title></svelte:head>

<AdminLayout title="Newsletters">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <!-- The two sides, each with its status then its date, in that order — no
         single badge, because one badge over two pipelines is the confusion this
         whole module exists to remove. The HTML body and the title map are
         deliberately absent: one is a full email, the other a form concern. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Newsletter"
        id={viewing?.id}
        heading={viewing?.name}
        fields={[
            { label: 'Publish on website', value: viewing?.is_published ? 'Yes' : 'No' },
            { label: 'Website status', value: viewing ? (NEWSLETTER_PUBLISH_STATUS_LABELS[viewing.published_status] ?? viewing.published_status) : '' },
            dateField('Publish at', viewing?.published_at),
            { label: 'Send by email', value: viewing?.is_sendable ? 'Yes' : 'No' },
            { label: 'Email status', value: viewing ? (NEWSLETTER_SEND_STATUS_LABELS[viewing.sent_status] ?? viewing.sent_status) : '' },
            dateField('Send at', viewing?.sent_at),
            { label: 'Subject', value: viewing?.subject || '' },
            { label: 'Groups', value: viewing?.groups?.length ? viewing.groups.map((g) => g.name).join(', ') : '' },
            { label: 'Integration', value: viewing?.integration?.name ?? 'App default mailer' },
            { label: 'File', value: viewing?.file_name || '' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="newsletter" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search newsletters…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('newsletters.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add newsletter
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
    <NewsletterForm newsletter={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No newsletters yet"
        emptyBody="Create a newsletter to get started."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="200px" title={row.name} />
        </span>
    {:else if column.key === 'channels'}
        <!-- At least one is always on, so this cell is never empty. Each badge is
             coloured by its OWN status, with that status named in the tooltip so
             the colour is never the only signal. -->
        <div class="flex flex-wrap items-center gap-1">
            {#if row.is_published}
                <span title="Website: {NEWSLETTER_PUBLISH_STATUS_LABELS[row.published_status] ?? row.published_status}">
                    <Badge variant={NEWSLETTER_PUBLISH_STATUS_VARIANTS[row.published_status] ?? 'secondary'}>Website</Badge>
                </span>
            {/if}
            {#if row.is_sendable}
                <span title="Email: {NEWSLETTER_SEND_STATUS_LABELS[row.sent_status] ?? row.sent_status}">
                    <Badge variant={NEWSLETTER_SEND_STATUS_VARIANTS[row.sent_status] ?? 'secondary'}>Email</Badge>
                </span>
            {/if}
        </div>
    {:else if column.key === 'published_status'}
        <Badge variant={NEWSLETTER_PUBLISH_STATUS_VARIANTS[row.published_status] ?? 'secondary'}>
            {NEWSLETTER_PUBLISH_STATUS_LABELS[row.published_status] ?? row.published_status}
        </Badge>
    {:else if column.key === 'sent_status'}
        <Badge variant={NEWSLETTER_SEND_STATUS_VARIANTS[row.sent_status] ?? 'secondary'}>
            {NEWSLETTER_SEND_STATUS_LABELS[row.sent_status] ?? row.sent_status}
        </Badge>
    {:else if column.key === 'sent_at'}
        {#if row.sent_at}<DateTime value={row.sent_at} />{:else}{/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <!-- No lock on a sent issue: the backend guard is gone deliberately, and
         re-sending is confirmed in the form rather than blocked here. -->
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('newsletters.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('newsletters.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
