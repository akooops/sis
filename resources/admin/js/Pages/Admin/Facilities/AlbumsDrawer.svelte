<script>
    /**
     * Which photo albums appear on a venue's page.
     *
     * PivotDrawer's BULK shape — no `form` snippet, because the link is two
     * foreign keys and there is nothing on it to edit. A multi-select attaches
     * several at once and every row carries a remove.
     *
     * `relation="album"` is both the `include=` the drawer sends and the key its
     * default cell reads, which is why the DTO's property is named for the
     * relation rather than for the column.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

    let { open = $bindable(false), facility = null } = $props();
</script>

<PivotDrawer
    bind:open
    title="Albums on {facility?.name ?? ''}"
    parentId={facility?.id}
    indexRoute="api.v1.admin.facility-albums.index"
    storeRoute="api.v1.admin.facility-albums.store"
    destroyRoute="api.v1.admin.facility-albums.destroy"
    resource="api.v1.admin.albums.index"
    payloadKey="albums"
    relation="album"
    width="w-[640px]"
    assignLabel="Attach albums"
    addLabel="Attach"
    searchPlaceholder="Search attached albums…"
    emptyTitle="Nothing attached yet"
    emptyBody="Attach an album and it will appear on this facility's page."
    confirmBody={(row) => `Remove ${row.album?.name ?? "this album"} from this facility? The album itself is untouched.`}
    columns={[
        { key: 'name', label: 'Album', truncate: false },
        { key: 'status', label: 'Status', truncate: false, width: '120px' },
    ]}
/>
