<script>
    /**
     * Newsletters index — issues published on the website, emailed to newsletter
     * groups by the scheduler, or both. Channels shows what an issue does, the
     * Website badge taking its colour from the publish pipeline; the Status column
     * is the send pipeline alone, so a publish-only issue stays draft forever.
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
        NEWSLETTER_STATUS_LABELS,
        NEWSLETTER_STATUS_VARIANTS,
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
        // What the issue actually does — the two switches, not the send pipeline.
        { key: 'channels', label: 'Channels', truncate: false },
        { key: 'subject', label: 'Subject', sortable: true, truncate: false },
        { key: 'groups', label: 'Groups', truncate: false },
        { key: 'file_name', label: 'File', truncate: false },
        { key: 'status', label: 'Status', sortable: true, truncate: false },
        { key: 'scheduled_at', label: 'Scheduled', sortable: true, truncate: false },
        { key: 'sent_at', label: 'Sent', sortable: true, truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'subject', label: 'Subject' },
        { value: 'publish_status', label: 'Publish status' },
        { value: 'published_at', label: 'Published' },
        { value: 'status', label: 'Status' },
        { value: 'scheduled_at', label: 'Scheduled' },
        { value: 'sent_at', label: 'Sent' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Every status is offered here —
    // filtering is a view of what exists, not a state the form may set.
    const filterConfig = [
        { key: 'is_published', type: 'boolean', label: 'Published on website' },
        { key: 'is_sendable', type: 'boolean', label: 'Sent by email' },
        {
            key: 'publish_status',
            type: 'select',
            label: 'Publish status',
            options: Object.entries(NEWSLETTER_PUBLISH_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(NEWSLETTER_STATUS_LABELS).map(([value, label]) => ({ value, label })),
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

    // Sending/sent 422 on both update and destroy — don't offer a door that is locked.
    const locked = (n) => n.status === 'sending' || n.status === 'sent';

    const dateField = (label, value) => (value ? { label, date: value } : { label, value: '—' });

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
    <!-- The send status is the badge, the publish one a field. The HTML body and
         the title map are deliberately absent — one is a full email, the other a
         form concern. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Newsletter"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: NEWSLETTER_STATUS_LABELS[viewing.status] ?? viewing.status, variant: NEWSLETTER_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={[
            { label: 'Publish on website', value: viewing?.is_published ? 'Yes' : 'No' },
            { label: 'Send by email', value: viewing?.is_sendable ? 'Yes' : 'No' },
            { label: 'File', value: viewing?.file_name || '—' },
            { label: 'Subject', value: viewing?.subject || '—' },
            { label: 'Groups', value: viewing?.groups?.length ? viewing.groups.map((g) => g.name).join(', ') : '—' },
            { label: 'Integration', value: viewing?.integration?.name ?? 'App default mailer' },
            // The website pipeline, then the email one — side by side, never merged.
            { label: 'Publish status', value: viewing ? (NEWSLETTER_PUBLISH_STATUS_LABELS[viewing.publish_status] ?? viewing.publish_status) : '—' },
            dateField('Published at', viewing?.published_at),
            dateField('Scheduled at', viewing?.scheduled_at),
            dateField('Sent at', viewing?.sent_at),
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
        <!-- At least one is always on, so this cell is never empty. Website is
             coloured by its publish status, with the label in the tooltip so the
             colour is never the only signal. -->
        <div class="flex flex-wrap items-center gap-1">
            {#if row.is_published}
                <span title="Website: {NEWSLETTER_PUBLISH_STATUS_LABELS[row.publish_status] ?? row.publish_status}">
                    <Badge variant={NEWSLETTER_PUBLISH_STATUS_VARIANTS[row.publish_status] ?? 'secondary'}>Website</Badge>
                </span>
            {/if}
            {#if row.is_sendable}<Badge variant="success">Email</Badge>{/if}
        </div>
    {:else if column.key === 'subject'}
        <!-- A publish-only issue has no subject line. -->
        {#if row.subject}
            <ClampText value={row.subject} maxWidth="240px" title={row.subject} />
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'file_name'}
        {#if row.file_url}
            <!-- stopPropagation, or the row click opens the drawer behind the download. -->
            <a
                href={row.file_url}
                target="_blank"
                rel="noreferrer noopener"
                class="kt-link inline-flex items-center gap-1.5 text-sm"
                onclick={(e) => e.stopPropagation()}
            >
                <i class="ki-filled ki-document shrink-0"></i>
                <ClampText value={row.file_name ?? 'Download'} maxWidth="180px" title={row.file_name} />
            </a>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else if column.key === 'groups'}
        {#if !row.groups?.length}
            <span class="text-xs text-muted-foreground">—</span>
        {:else if row.groups.length > 2}
            <!-- Past a couple, the names stop being readable in a cell — the drawer lists them. -->
            <Badge variant="primary">{row.groups.length} groups</Badge>
        {:else}
            <div class="flex flex-wrap items-center gap-1">
                {#each row.groups as group (group.id)}
                    <Badge variant="primary">
                        <ClampText value={group.name} maxWidth="120px" title={group.name} />
                    </Badge>
                {/each}
            </div>
        {/if}
    {:else if column.key === 'status'}
        <Badge variant={NEWSLETTER_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {NEWSLETTER_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'scheduled_at'}
        {#if row.scheduled_at}<DateTime value={row.scheduled_at} />{:else}<span class="text-xs text-muted-foreground">—</span>{/if}
    {:else if column.key === 'sent_at'}
        {#if row.sent_at}<DateTime value={row.sent_at} />{:else}<span class="text-xs text-muted-foreground">—</span>{/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        !locked(row) && hasPermission('newsletters.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        !locked(row) && hasPermission('newsletters.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
