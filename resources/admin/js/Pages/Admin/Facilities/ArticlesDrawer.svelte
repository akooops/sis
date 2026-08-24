<script>
    /**
     * Which news items appear on a venue's page.
     *
     * PivotDrawer's BULK shape — no `form` snippet, because the link is two
     * foreign keys and there is nothing on it to edit. A multi-select attaches
     * several at once and every row carries a remove.
     *
     * `relation="article"` is both the `include=` the drawer sends and the key its
     * default cell reads, which is why the DTO's property is named for the
     * relation rather than for the column.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

    let { open = $bindable(false), facility = null } = $props();
</script>

<PivotDrawer
    bind:open
    title="News on {facility?.name ?? ''}"
    parentId={facility?.id}
    indexRoute="api.v1.admin.facility-articles.index"
    storeRoute="api.v1.admin.facility-articles.store"
    destroyRoute="api.v1.admin.facility-articles.destroy"
    resource="api.v1.admin.articles.index"
    payloadKey="articles"
    relation="article"
    width="w-[640px]"
    assignLabel="Attach news"
    addLabel="Attach"
    searchPlaceholder="Search attached news…"
    emptyTitle="Nothing attached yet"
    emptyBody="Attach an article and it will appear on this facility's page."
    confirmBody={(row) => `Remove ${row.article?.name ?? 'this article'} from this facility? The article itself is untouched.`}
    columns={[
        { key: 'name', label: 'Article', truncate: false },
        { key: 'status', label: 'Status', truncate: false, width: '120px' },
    ]}
/>
