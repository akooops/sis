<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedPurchaseOrder = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#purchase_order_view_drawer"]');
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
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="purchase_order_view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Purchase Order Details</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedPurchaseOrder}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                    <span class="text-sm font-medium">#{selectedPurchaseOrder.id}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                    <span class="text-sm font-medium text-mono">{selectedPurchaseOrder.name}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                    {#if selectedPurchaseOrder.code}
                        <span class="text-sm font-medium text-mono">{selectedPurchaseOrder.code}</span>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Status:</span>
                    <span class="kt-badge kt-badge-outline {getStatusBadgeClass(selectedPurchaseOrder.status)} text-xs font-medium capitalize">
                        {formatStatus(selectedPurchaseOrder.status)}
                    </span>
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Supplier:</span>
                    {#if selectedPurchaseOrder.supplier}
                        <div class="flex items-center gap-3 max-content">
                            <img src={selectedPurchaseOrder.supplier.logo_url} alt={selectedPurchaseOrder.supplier.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-secondary-foreground">{selectedPurchaseOrder.supplier.name}</span>
                                {#if selectedPurchaseOrder.supplier.code}
                                    <span class="text-xs text-muted-foreground">{selectedPurchaseOrder.supplier.code}</span>
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Site:</span>
                    {#if selectedPurchaseOrder.production_site}
                        <div class="flex items-center gap-3 max-content">
                            <img src={selectedPurchaseOrder.production_site.logo_url} alt={selectedPurchaseOrder.production_site.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-secondary-foreground">{selectedPurchaseOrder.production_site.name}</span>
                                {#if selectedPurchaseOrder.production_site.code}
                                    <span class="text-xs text-muted-foreground">{selectedPurchaseOrder.production_site.code}</span>
                                {/if}
                            </div>
                        </div>
                    {:else}
                        <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                    {/if}
                </div>

                {#if selectedPurchaseOrder.description}
                    <div class="space-y-1">
                        <span class="text-sm font-medium text-muted-foreground">Description:</span>
                        <p class="text-sm text-secondary-foreground">{selectedPurchaseOrder.description}</p>
                    </div>
                {/if}

                <div class="border-b border-border w-full"></div>

                <div class="space-y-4">
                    <span class="text-sm font-medium text-muted-foreground">Documents:</span>
                    {#if selectedPurchaseOrder.documents?.length}
                        <div class="space-y-2">
                            {#each selectedPurchaseOrder.documents as document}
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
                    {/if}
                </div>

                <div class="border-b border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedPurchaseOrder.created_at)}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <span class="text-sm font-medium">{formatTimeStamp(selectedPurchaseOrder.updated_at)}</span>
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
                        value={selectedPurchaseOrder.id.toString()}
                        title="Purchase Order ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
