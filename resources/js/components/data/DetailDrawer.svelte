<script>
    /**
     * DetailDrawer — the standard read-only record drawer. Shows the full id as a
     * `#…` row (brand primary), then a list of label/value fields, an optional
     * `header` snippet (avatar/status) and `children` (module extras), and a
     * toggle that reveals the barcode + QR code for the id.
     *
     *   <DetailDrawer bind:open title="User" id={user?.id}
     *       fields={[{ label, value }, { label, date }]}>
     *       {#snippet header()}…{/snippet}
     *   </DetailDrawer>
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import BarcodeQRGenerator from './BarcodeQRGenerator.svelte';

    let {
        open = $bindable(false),
        title = null,
        id = null,
        fields = [],
        createdAt = null,
        updatedAt = null,
        header,
        children,
    } = $props();

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

<Drawer bind:open {title}>
    {#if id != null}
        <div class="flex flex-col gap-5">
            {#if header}
                {@render header()}
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
                                <ClampText value={field.value ?? '—'} lines={4} toggle copy />
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
