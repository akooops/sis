<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedPurchaseOrderLine = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#purchase_order_line_view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'confirmed') return 'kt-badge-success';
        if (status === 'cancelled') return 'kt-badge-destructive';
        return 'kt-badge-secondary';
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="purchase_order_line_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Purchase Order Line Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedPurchaseOrderLine}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedPurchaseOrderLine.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Line:</span>
                    <span class="text-sm font-medium text-mono">{selectedPurchaseOrderLine.line}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                    {#if selectedPurchaseOrderLine.code}
                        <span class="text-sm font-medium text-mono">{selectedPurchaseOrderLine.code}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(selectedPurchaseOrderLine.status)} text-xs font-medium capitalize">
                        {formatStatus(selectedPurchaseOrderLine.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                {#if selectedPurchaseOrderLine.purchase_order}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Purchase Order:</span>
                        <div class="flex flex-col gap-1 text-end">
                            <span class="text-sm font-medium text-secondary-foreground">{selectedPurchaseOrderLine.purchase_order.name}</span>
                            {#if selectedPurchaseOrderLine.purchase_order.code}
                                <span class="text-xs text-muted-foreground">{selectedPurchaseOrderLine.purchase_order.code}</span>
                            {/if}
                        </div>
                    </div>
                {/if}

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedPurchaseOrderLine.product}
                        <div class="flex items-center gap-3 max-content">
                            <img src={selectedPurchaseOrderLine.product.image_url} alt={selectedPurchaseOrderLine.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-secondary-foreground">{selectedPurchaseOrderLine.product.name}</span>
                                {#if selectedPurchaseOrderLine.product.code}
                                    <span class="text-xs text-muted-foreground">{selectedPurchaseOrderLine.product.code}</span>
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Quantities</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Ordered:</span>
                        {#if formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity)}
                            <span class="text-sm font-medium">{formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Shipped:</span>
                        {#if formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_shipped)}
                            <span class="text-sm font-medium">{formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_shipped)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Received:</span>
                        {#if formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_received)}
                            <span class="text-sm font-medium">{formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_received)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Approved:</span>
                        {#if formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_approved)}
                            <span class="text-sm font-medium">{formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_approved)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Released:</span>
                        {#if formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_released)}
                            <span class="text-sm font-medium">{formatQuantityWithUnit(selectedPurchaseOrderLine.product, selectedPurchaseOrderLine.quantity_released)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                {#if selectedPurchaseOrderLine.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedPurchaseOrderLine.notes}</p>
                    </div>
                {/if}

                {#if selectedPurchaseOrderLine.llm_documents_summary}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Documents Summary:</span>
                        <p class="text-sm text-secondary-foreground">{selectedPurchaseOrderLine.llm_documents_summary}</p>
                    </div>
                {/if}

                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Documents</span>
                    </div>
                    <div class="kt-card-content p-4">
                        {#if selectedPurchaseOrderLine.documents?.length}
                            <div class="space-y-2">
                                {#each selectedPurchaseOrderLine.documents as document}
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
                    <span class="text-sm font-medium">{formatTimeStamp(selectedPurchaseOrderLine.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedPurchaseOrderLine.updated_at)}</span>
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
                        value={selectedPurchaseOrderLine.id.toString()}
                        title="Purchase Order Line ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
