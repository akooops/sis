<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedProductionOrder = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#production_order_view_drawer"]')?.click();
    }

    function getStatusBadgeClass(status) {
        if (status === 'pending') return 'kt-badge-warning';
        if (status === 'confirmed') return 'kt-badge-info';
        if (status === 'producing') return 'kt-badge-primary';
        if (status === 'completed') return 'kt-badge-success';
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

    function formatDateTime(value) {
        if (!value) return null;
        return formatTimeStamp(value);
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="production_order_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Production Order Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedProductionOrder}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedProductionOrder.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Batch:</span>
                    {#if selectedProductionOrder.batch}
                        <span class="text-sm font-medium text-mono">{selectedProductionOrder.batch}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(selectedProductionOrder.status)} text-xs font-medium capitalize">
                        {formatStatus(selectedProductionOrder.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Product:</span>
                    {#if selectedProductionOrder.product}
                        <div class="flex items-center gap-3 max-content">
                            <img src={selectedProductionOrder.product.image_url} alt={selectedProductionOrder.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-secondary-foreground">{selectedProductionOrder.product.name}</span>
                                {#if selectedProductionOrder.product.code}
                                    <span class="text-xs text-muted-foreground">{selectedProductionOrder.product.code}</span>
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Qty planned:</span>
                    {#if formatQuantityWithUnit(selectedProductionOrder.product, selectedProductionOrder.quantity_planned)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedProductionOrder.product, selectedProductionOrder.quantity_planned)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Qty produced:</span>
                    {#if formatQuantityWithUnit(selectedProductionOrder.product, selectedProductionOrder.quantity_produced)}
                        <span class="text-sm font-medium">{formatQuantityWithUnit(selectedProductionOrder.product, selectedProductionOrder.quantity_produced)}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Schedule</span>

                    {#if selectedProductionOrder.planned_at}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Planned at:</span>
                            <span class="text-sm font-medium">{formatDateTime(selectedProductionOrder.planned_at)}</span>
                        </div>
                    {/if}

                    {#if selectedProductionOrder.actual_at}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground min-w-20">Actual at:</span>
                            <span class="text-sm font-medium">{formatDateTime(selectedProductionOrder.actual_at)}</span>
                        </div>
                    {/if}
                </div>

                {#if selectedProductionOrder.notes}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Notes:</span>
                        <p class="text-sm text-secondary-foreground">{selectedProductionOrder.notes}</p>
                    </div>
                {/if}

                {#if selectedProductionOrder.llm_documents_summary}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Documents summary:</span>
                        <p class="text-sm text-secondary-foreground whitespace-pre-wrap">{selectedProductionOrder.llm_documents_summary}</p>
                    </div>
                {/if}

                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Documents</span>
                    </div>
                    <div class="kt-card-content p-4">
                        {#if selectedProductionOrder.documents?.length}
                            <div class="space-y-2">
                                {#each selectedProductionOrder.documents as document}
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
                    <span class="text-sm font-medium">{formatTimeStamp(selectedProductionOrder.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedProductionOrder.updated_at)}</span>
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
                        value={selectedProductionOrder.id.toString()}
                        title="Production Order ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
