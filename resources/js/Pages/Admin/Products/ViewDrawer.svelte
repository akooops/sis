<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedProduct = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }

    function formatProductType(type) {
        if (type === 'raw_material') return 'Raw Material';
        if (type === 'finished_product') return 'Finished Product';
        if (type === 'component') return 'Component';
        return null;
    }

    function getTypeBadgeClass(type) {
        if (type === 'raw_material') return 'kt-badge-info';
        if (type === 'finished_product') return 'kt-badge-success';
        if (type === 'component') return 'kt-badge-warning';
        return 'kt-badge-secondary';
    }

    function formatQuantity(product, value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    function formatDays(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        return `${value} days`;
    }

    function formatPercent(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        return `${value}%`;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Product Details
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedProduct}
            <div class="space-y-4">
                <div class="space-y-3">
                    <img
                        src={selectedProduct.image_url}
                        alt={selectedProduct.name}
                        class="w-[100px] h-[100px] rounded-lg object-cover"
                    />

                    <div class="border-b border-border w-full"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                        <span class="text-sm font-medium word-break">#{selectedProduct.id}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                        <span class="text-sm font-medium text-mono word-break">{selectedProduct.name}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                        {#if selectedProduct.code}
                            <span class="text-sm font-medium text-mono word-break">{selectedProduct.code}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Type:</span>
                        {#if formatProductType(selectedProduct.type)}
                            <span class="kt-badge kt-badge-outline {getTypeBadgeClass(selectedProduct.type)} text-xs font-medium capitalize">
                                {formatProductType(selectedProduct.type)}
                            </span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Description:</span>
                        {#if selectedProduct.description}
                            <span class="text-sm font-medium text-mono word-break text-end">{selectedProduct.description}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Unit:</span>
                        {#if selectedProduct.unit_of_measurement}
                            <span class="text-sm font-medium text-mono word-break">{selectedProduct.unit_of_measurement}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Preferred supplier:</span>
                        {#if selectedProduct.preferred_supplier}
                            <span class="text-sm font-medium text-mono word-break">{selectedProduct.preferred_supplier.name}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Lead Times</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Supply:</span>
                        {#if formatDays(selectedProduct.supply_lead_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.supply_lead_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Warehouse receipt:</span>
                        {#if formatDays(selectedProduct.warehouse_goods_receipt_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.warehouse_goods_receipt_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">QA inspection:</span>
                        {#if formatDays(selectedProduct.qa_inspection_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.qa_inspection_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">QC inspection:</span>
                        {#if formatDays(selectedProduct.qc_inspection_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.qc_inspection_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">QP inspection:</span>
                        {#if formatDays(selectedProduct.qp_inspection_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.qp_inspection_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Production</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Batch size:</span>
                        {#if formatQuantity(selectedProduct, selectedProduct.batch_size)}
                            <span class="text-sm font-medium text-mono word-break">{formatQuantity(selectedProduct, selectedProduct.batch_size)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Scrap rate:</span>
                        {#if formatPercent(selectedProduct.scrap_rate)}
                            <span class="text-sm font-medium text-mono word-break">{formatPercent(selectedProduct.scrap_rate)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Shelf Life</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Has shelf life:</span>
                        <span class="kt-badge kt-badge-outline {selectedProduct.has_shelf_life ? 'kt-badge-success' : 'kt-badge-secondary'} text-xs font-medium capitalize">
                            {selectedProduct.has_shelf_life ? 'Yes' : 'No'}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Shelf life time:</span>
                        {#if formatDays(selectedProduct.shelf_life_time)}
                            <span class="text-sm font-medium text-mono word-break">{formatDays(selectedProduct.shelf_life_time)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Stock</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Current stock:</span>
                        {#if formatQuantity(selectedProduct, selectedProduct.current_stock)}
                            <span class="text-sm font-medium text-mono word-break">{formatQuantity(selectedProduct, selectedProduct.current_stock)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Min stock:</span>
                        {#if formatQuantity(selectedProduct, selectedProduct.min_stock)}
                            <span class="text-sm font-medium text-mono word-break">{formatQuantity(selectedProduct, selectedProduct.min_stock)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Max stock:</span>
                        {#if formatQuantity(selectedProduct, selectedProduct.max_stock)}
                            <span class="text-sm font-medium text-mono word-break">{formatQuantity(selectedProduct, selectedProduct.max_stock)}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedProduct.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedProduct.updated_at)}</span>
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
                        value={selectedProduct.id.toString()}
                        title="Product ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {:else}
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-box text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No Product Selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select a product to view its details.
                </p>
            </div>
        {/if}
    </div>
</div>
