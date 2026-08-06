<script>
    /**
     * DetailDrawer — the ONE read-only record drawer. Every module uses it; a
     * module wanting an avatar or its files does not get a drawer of its own,
     * it passes more props. Top to bottom: an optional avatar/heading block, the
     * full id as a `#…` row (brand primary), the label/value fields, any media
     * collections as thumbnail grids, `children` for genuine one-offs, and a
     * toggle revealing the barcode + QR code for the id.
     *
     *   <DetailDrawer bind:open title="User" id={user?.id}
     *       avatar={{ src: user?.avatar_url, name: 'Ada Lovelace' }}
     *       heading="Ada Lovelace"
     *       badge={{ label: 'Approved', variant: 'success' }}
     *       fields={[{ label, value }, { label, date }]}
     *       collections={[{ label: 'Attachments', items: [media, …] }]} />
     *
     * `header` remains for a heading no combination of props can express; prefer
     * the props, so every drawer in the app keeps the same shape.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import MediaThumb from '@/components/media/MediaThumb.svelte';
    import BarcodeQRGenerator from './BarcodeQRGenerator.svelte';

    let {
        open = $bindable(false),
        title = null,
        id = null,
        fields = [],
        avatar = null,
        heading = null,
        subheading = null,
        badge = null,
        collections = [],
        width = undefined,
        createdAt = null,
        updatedAt = null,
        header,
        children,
    } = $props();

    // Only render a collection that actually has files — an empty grid is a
    // heading over nothing.
    const shownCollections = $derived((collections ?? []).filter((c) => c?.items?.length));

    let showCodes = $state(false);

    // Reset the codes panel each time the drawer closes.
    $effect(() => {
        if (!open) showCodes = false;
    });

    // Standard timestamp rows appended after the record's own fields.
    const timestamps = $derived(
        [
            createdAt ? { label: 'Created', date: createdAt } : null,
            updatedAt ? { label: 'Updated', date: updatedAt } : null,
        ].filter(Boolean),
    );
</script>

<Drawer bind:open {title} {width}>
    {#if id != null}
        <div class="flex flex-col gap-5">
            {#if header}
                {@render header()}
            {:else if avatar || heading || badge}
                <div class="flex min-w-0 items-center gap-3">
                    {#if avatar}
                        <Avatar src={avatar.src} name={avatar.name ?? heading ?? ''} size="lg" />
                    {/if}
                    <!-- flex-1 + min-w-0 on the column, and w-full on each row
                         inside it: with `items-start` a child sizes to its content,
                         so an unbroken 200-char name would otherwise push past the
                         drawer and scroll the whole panel sideways. -->
                    <div class="flex min-w-0 flex-1 flex-col items-start gap-1">
                        {#if heading}
                            <div class="w-full min-w-0 text-base font-semibold text-mono">
                                <ClampText value={heading} lines={2} title={heading} />
                            </div>
                        {/if}
                        {#if subheading}
                            <div class="w-full min-w-0 text-xs text-muted-foreground">
                                <ClampText value={subheading} title={subheading} />
                            </div>
                        {/if}
                        {#if badge}
                            <Badge variant={badge.variant ?? 'secondary'}>{badge.label}</Badge>
                        {/if}
                    </div>
                </div>
            {/if}

            <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="text-muted-foreground shrink-0">ID</dt>
                    <dd class="font-mono font-medium text-primary break-all text-end">#{id}</dd>
                </div>
                {#each fields as field}
                    <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="text-muted-foreground shrink-0">{field.label}</dt>
                        <dd class="text-mono min-w-0 max-w-[65%]">
                            {#if field.date}
                                <DateTime value={field.date} />
                            {:else}
                                <ClampText value={field.value ?? ''} lines={4} toggle copy />
                            {/if}
                        </dd>
                    </div>
                {/each}
                {#each timestamps as ts}
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="text-muted-foreground shrink-0">{ts.label}</dt>
                        <dd class="text-end {ts.danger ? 'text-destructive' : 'text-mono'}">
                            <DateTime value={ts.date} />
                        </dd>
                    </div>
                {/each}
            </dl>

            {#each shownCollections as collection (collection.label)}
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-mono">{collection.label}</span>
                    <div class="grid grid-cols-4 gap-2">
                        {#each collection.items as item (item.id)}
                            <a
                                class="flex flex-col gap-1 rounded-lg border border-border p-1.5 hover:border-primary"
                                href={item.url ?? null}
                                target="_blank"
                                rel="noreferrer"
                                title={item.name}
                            >
                                <div class="flex aspect-square items-center justify-center overflow-hidden rounded bg-muted">
                                    <MediaThumb {item} iconSize="text-xl" />
                                </div>
                                <span class="truncate text-[10px] text-muted-foreground">{item.name}</span>
                            </a>
                        {/each}
                    </div>
                </div>
            {/each}

            {#if children}
                {@render children()}
            {/if}

            <div class="border-t border-border"></div>

            <div>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (showCodes = !showCodes)}>
                    <i class="ki-filled ki-barcode"></i>
                    {showCodes ? 'Hide Barcode & QR Code' : 'Show Barcode & QR Code'}
                </button>
            </div>

            {#if showCodes}
                <BarcodeQRGenerator value={String(id)} />
            {/if}
        </div>
    {/if}
</Drawer>
