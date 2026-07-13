<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedShipping = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#shipping_view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }

    function getStatusBadgeClass(status) {
        if (status === 'in_transit') return 'kt-badge-warning';
        if (status === 'at_customs') return 'kt-badge-info';
        if (status === 'delivered') return 'kt-badge-primary';
        if (status === 'received') return 'kt-badge-success';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatTransportMode(mode) {
        if (!mode) return 'N/A';
        return mode.charAt(0).toUpperCase() + mode.slice(1);
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="shipping_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Shipping Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedShipping}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedShipping.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                    {#if selectedShipping.code}
                        <span class="text-sm font-medium text-mono">{selectedShipping.code}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(selectedShipping.status)} text-xs font-medium capitalize">
                        {formatStatus(selectedShipping.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                {#if selectedShipping.purchase_order_line}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">PO Line:</span>
                        <div class="flex flex-col gap-1 text-end">
                            <span class="text-sm font-medium text-secondary-foreground">{selectedShipping.purchase_order_line.line}</span>
                            {#if selectedShipping.purchase_order_line.code}
                                <span class="text-xs text-muted-foreground">{selectedShipping.purchase_order_line.code}</span>
                            {/if}
                        </div>
                    </div>
                {/if}

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedShipping.product}
                        <div class="flex items-center gap-3 max-content">
                            <img src={selectedShipping.product.image_url} alt={selectedShipping.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-secondary-foreground">{selectedShipping.product.name}</span>
                                {#if selectedShipping.product.code}
                                    <span class="text-xs text-muted-foreground">{selectedShipping.product.code}</span>
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Quantity:</span>
                    {#if formatQuantityWithUnit(selectedShipping.product, selectedShipping.quantity)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedShipping.product, selectedShipping.quantity)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Transport:</span>
                    <span class="text-sm font-medium">{formatTransportMode(selectedShipping.mode_of_transportation)}</span>
                </div>

                {#if selectedShipping.carrier}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Carrier:</span>
                        <span class="text-sm font-medium">{selectedShipping.carrier}</span>
                    </div>
                {/if}

                {#if selectedShipping.tracking_number}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Tracking:</span>
                        <span class="text-sm font-medium text-mono">{selectedShipping.tracking_number}</span>
                    </div>
                {/if}

                <div class="border-b border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Delivery Dates</span>

                    {#if selectedShipping.planned_delivery_at_customs}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Planned Customs:</span>
                            <span class="text-sm font-medium">{selectedShipping.planned_delivery_at_customs}</span>
                        </div>
                    {/if}

                    {#if selectedShipping.planned_delivery_at_site}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Planned Site:</span>
                            <span class="text-sm font-medium">{selectedShipping.planned_delivery_at_site}</span>
                        </div>
                    {/if}

                    {#if selectedShipping.actual_delivery_at_customs}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Actual Customs:</span>
                            <span class="text-sm font-medium">{selectedShipping.actual_delivery_at_customs}</span>
                        </div>
                    {/if}

                    {#if selectedShipping.actual_delivery_at_site}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Actual Site:</span>
                            <span class="text-sm font-medium">{selectedShipping.actual_delivery_at_site}</span>
                        </div>
                    {/if}

                    {#if selectedShipping.received_at_site_date}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Received At Site:</span>
                            <span class="text-sm font-medium">{selectedShipping.received_at_site_date}</span>
                        </div>
                    {/if}
                </div>

                {#if selectedShipping.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedShipping.notes}</p>
                    </div>
                {/if}

                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Documents</span>
                    </div>
                    <div class="kt-card-content p-4">
                        {#if selectedShipping.documents?.length}
                            <div class="space-y-2">
                                {#each selectedShipping.documents as document}
                                    <a
                                        href={document.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm hover:text-primary"
                                    >
                                        <i class="fa-solid fa-file text-muted-foreground shrink-0"></i>
                                        <span class="truncate">{document.original_name}</span>
                                    </a>
                                {/each}
                            </div>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedShipping.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedShipping.updated_at)}</span>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex">
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm"
                        class:kt-btn-secondary={!showCodeGenerator}
                        class:kt-btn-ghost={showCodeGenerator}
                        on:click={() => showCodeGenerator = !showCodeGenerator}
                    >
                        <i class="fa-solid fa-qrcode mr-2"></i>
                        {showCodeGenerator ? 'Hide' : 'Show'} Barcode & QR Code
                    </button>
                </div>

                {#if showCodeGenerator}
                    <BarcodeQRGenerator
                        value={selectedShipping.id.toString()}
                        title="Shipping ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
