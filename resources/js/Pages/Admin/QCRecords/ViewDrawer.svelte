<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';
    import { formatWarehouseStatus, getWarehouseStatusBadgeClass } from '../Warehouse/recordActions.js';

    export let selectedQcRecord = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#qc_record_view_drawer"]')?.click();
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="qc_record_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">QC Record Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedQcRecord}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedQcRecord.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                    {#if selectedQcRecord.code}
                        <span class="text-sm font-medium text-mono">{selectedQcRecord.code}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getWarehouseStatusBadgeClass(selectedQcRecord.status)} text-xs font-medium capitalize">
                        {formatWarehouseStatus(selectedQcRecord.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                {#if selectedQcRecord.w_h_good_receipt}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">WH Receipt:</span>
                        <span class="text-sm font-medium">{selectedQcRecord.w_h_good_receipt.code || `#${selectedQcRecord.w_h_good_receipt.id}`}</span>
                    </div>
                {/if}

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedQcRecord.product}
                        <div class="flex items-center gap-3">
                            <img src={selectedQcRecord.product.image_url} alt={selectedQcRecord.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <span class="text-sm font-medium">{selectedQcRecord.product.name}</span>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Quantity:</span>
                    {#if formatQuantityWithUnit(selectedQcRecord.product, selectedQcRecord.quantity)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedQcRecord.product, selectedQcRecord.quantity)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">QC Dates</span>
                    {#if selectedQcRecord.planned_qc_date}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Planned:</span>
                            <span class="text-sm font-medium">{selectedQcRecord.planned_qc_date}</span>
                        </div>
                    {/if}
                    {#if selectedQcRecord.actual_qc_date}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Actual:</span>
                            <span class="text-sm font-medium">{selectedQcRecord.actual_qc_date}</span>
                        </div>
                    {/if}
                </div>

                {#if selectedQcRecord.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedQcRecord.notes}</p>
                    </div>
                {/if}

                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Documents</span>
                    </div>
                    <div class="kt-card-content p-4">
                        {#if selectedQcRecord.documents?.length}
                            <div class="space-y-2">
                                {#each selectedQcRecord.documents as document}
                                    <a href={document.url} target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm hover:text-primary">
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
                    <span class="text-sm font-medium">{formatTimeStamp(selectedQcRecord.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedQcRecord.updated_at)}</span>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex">
                    <button type="button" class="kt-btn kt-btn-sm" class:kt-btn-secondary={!showCodeGenerator} class:kt-btn-ghost={showCodeGenerator} on:click={() => showCodeGenerator = !showCodeGenerator}>
                        <i class="fa-solid fa-qrcode mr-2"></i>
                        {showCodeGenerator ? 'Hide' : 'Show'} Barcode & QR Code
                    </button>
                </div>

                {#if showCodeGenerator}
                    <BarcodeQRGenerator value={selectedQcRecord.id.toString()} title="QC Record ID Codes" showTitle={true} />
                {/if}
            </div>
        {/if}
    </div>
</div>
