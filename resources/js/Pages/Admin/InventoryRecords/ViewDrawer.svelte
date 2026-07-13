<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';
    import { formatInventoryType, getInventoryTypeBadgeClass } from '../Warehouse/recordActions.js';

    export let selectedInventoryRecord = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#inventory_record_view_drawer"]')?.click();
    }

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="inventory_record_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Inventory Record Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedInventoryRecord}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium text-mono">#{selectedInventoryRecord.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Type:</span>
                    <span class="kt-badge kt-badge-outline {getInventoryTypeBadgeClass(selectedInventoryRecord.type)} text-xs font-medium capitalize">
                        {formatInventoryType(selectedInventoryRecord.type)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedInventoryRecord.product}
                        <div class="flex items-center gap-3">
                            <img src={selectedInventoryRecord.product.image_url} alt={selectedInventoryRecord.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <span class="text-sm font-medium">{selectedInventoryRecord.product.name}</span>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Quantity:</span>
                    {#if formatQuantityWithUnit(selectedInventoryRecord.product, selectedInventoryRecord.quantity)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedInventoryRecord.product, selectedInventoryRecord.quantity)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Site:</span>
                    {#if selectedInventoryRecord.production_site}
                        <span class="kt-badge kt-badge-outline kt-badge-info text-xs font-medium">{selectedInventoryRecord.production_site.name}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                {#if selectedInventoryRecord.q_p_record}
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">QP Record:</span>
                        <span class="text-sm font-medium text-mono">{selectedInventoryRecord.q_p_record.code || `#${selectedInventoryRecord.q_p_record.id}`}</span>
                    </div>
                {/if}

                {#if selectedInventoryRecord.expiry_date}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Expiry:</span>
                        <span class="text-sm font-medium">{selectedInventoryRecord.expiry_date}</span>
                    </div>
                {/if}

                {#if selectedInventoryRecord.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedInventoryRecord.notes}</p>
                    </div>
                {/if}

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedInventoryRecord.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedInventoryRecord.updated_at)}</span>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex">
                    <button type="button" class="kt-btn kt-btn-sm" class:kt-btn-secondary={!showCodeGenerator} class:kt-btn-ghost={showCodeGenerator} on:click={() => showCodeGenerator = !showCodeGenerator}>
                        <i class="fa-solid fa-qrcode mr-2"></i>
                        {showCodeGenerator ? 'Hide' : 'Show'} Barcode & QR Code
                    </button>
                </div>

                {#if showCodeGenerator}
                    <BarcodeQRGenerator value={selectedInventoryRecord.id.toString()} title="Inventory Record ID Codes" showTitle={true} />
                {/if}
            </div>
        {/if}
    </div>
</div>
