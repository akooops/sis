<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';

    export let selectedProductionLine = null;

    let showCodeGenerator = false;

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Production Line Details
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedProductionLine}
            <div class="space-y-4">
                <div class="space-y-3">
                    <div class="border-b border-border w-full"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                        <span class="text-sm font-medium word-break">#{selectedProductionLine.id}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                        <span class="text-sm font-medium text-mono word-break">{selectedProductionLine.name}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                        {#if selectedProductionLine.code}
                            <span class="text-sm font-medium text-mono word-break">{selectedProductionLine.code}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Description:</span>
                        {#if selectedProductionLine.description}
                            <span class="text-sm font-medium text-mono word-break text-end">{selectedProductionLine.description}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Site:</span>
                        {#if selectedProductionLine.production_site}
                            <span class="text-sm font-medium text-mono word-break">{selectedProductionLine.production_site.name}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Process:</span>
                        {#if selectedProductionLine.production_process}
                            <span class="text-sm font-medium text-mono word-break">{selectedProductionLine.production_process.name}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{formatTimeStamp(selectedProductionLine.created_at)}</span>
                    </div>
                </div>

                <div class="flex items-start justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{formatTimeStamp(selectedProductionLine.updated_at)}</span>
                    </div>
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
                        value={selectedProductionLine.id.toString()}
                        title="Production Line ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {:else}
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-conveyor-belt-boxes text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No Production Line Selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select a production line to view its details.
                </p>
            </div>
        {/if}
    </div>
</div>
