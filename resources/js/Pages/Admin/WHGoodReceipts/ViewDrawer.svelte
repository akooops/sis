<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedWhGoodReceipt = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#wh_good_receipt_view_drawer"]')?.click();
    }

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'received') return 'kt-badge-success';
        if (status === 'submitted_to_qc') return 'kt-badge-info';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="wh_good_receipt_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">WH Good Receipt Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedWhGoodReceipt}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedWhGoodReceipt.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                    {#if selectedWhGoodReceipt.code}
                        <span class="text-sm font-medium text-mono">{selectedWhGoodReceipt.code}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(selectedWhGoodReceipt.status)} text-xs font-medium capitalize">
                        {formatStatus(selectedWhGoodReceipt.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                {#if selectedWhGoodReceipt.shipping}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Shipping:</span>
                        <span class="text-sm font-medium">{selectedWhGoodReceipt.shipping.code || `#${selectedWhGoodReceipt.shipping.id}`}</span>
                    </div>
                {/if}

                {#if selectedWhGoodReceipt.purchase_order_line}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">PO Line:</span>
                        <span class="text-sm font-medium">{selectedWhGoodReceipt.purchase_order_line.code || selectedWhGoodReceipt.purchase_order_line.line}</span>
                    </div>
                {/if}

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedWhGoodReceipt.product}
                        <div class="flex items-center gap-3">
                            <img src={selectedWhGoodReceipt.product.image_url} alt={selectedWhGoodReceipt.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <span class="text-sm font-medium">{selectedWhGoodReceipt.product.name}</span>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Quantity:</span>
                    {#if formatQuantityWithUnit(selectedWhGoodReceipt.product, selectedWhGoodReceipt.quantity)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedWhGoodReceipt.product, selectedWhGoodReceipt.quantity)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                    {/if}
                </div>

                {#if selectedWhGoodReceipt.batch}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Batch:</span>
                        <span class="text-sm font-medium text-mono">{selectedWhGoodReceipt.batch}</span>
                    </div>
                {/if}

                {#if selectedWhGoodReceipt.expiry_date}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Expiry:</span>
                        <span class="text-sm font-medium">{selectedWhGoodReceipt.expiry_date}</span>
                    </div>
                {/if}

                <div class="border-b border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Receipt Dates</span>
                    {#if selectedWhGoodReceipt.planned_receipt_date}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Planned:</span>
                            <span class="text-sm font-medium">{selectedWhGoodReceipt.planned_receipt_date}</span>
                        </div>
                    {/if}
                    {#if selectedWhGoodReceipt.actual_receipt_date}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Actual:</span>
                            <span class="text-sm font-medium">{selectedWhGoodReceipt.actual_receipt_date}</span>
                        </div>
                    {/if}
                </div>

                {#if selectedWhGoodReceipt.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedWhGoodReceipt.notes}</p>
                    </div>
                {/if}

                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Documents</span>
                    </div>
                    <div class="kt-card-content p-4">
                        {#if selectedWhGoodReceipt.documents?.length}
                            <div class="space-y-2">
                                {#each selectedWhGoodReceipt.documents as document}
                                    <a href={document.url} target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm hover:text-primary">
                                        <i class="fa-solid fa-file text-muted-foreground shrink-0"></i>
                                        <span class="truncate">{document.original_name}</span>
                                    </a>
                                {/each}
                            </div>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedWhGoodReceipt.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedWhGoodReceipt.updated_at)}</span>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex">
                    <button type="button" class="kt-btn kt-btn-sm" class:kt-btn-secondary={!showCodeGenerator} class:kt-btn-ghost={showCodeGenerator} on:click={() => showCodeGenerator = !showCodeGenerator}>
                        <i class="fa-solid fa-qrcode mr-2"></i>
                        {showCodeGenerator ? 'Hide' : 'Show'} Barcode & QR Code
                    </button>
                </div>

                {#if showCodeGenerator}
                    <BarcodeQRGenerator value={selectedWhGoodReceipt.id.toString()} title="WH Good Receipt ID Codes" showTitle={true} />
                {/if}
            </div>
        {/if}
    </div>
</div>
