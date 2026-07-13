<script>
    import { createEventDispatcher } from 'svelte';
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';
    
    const dispatch = createEventDispatcher();
    
    // Permission data
    export let selectedPermission = null;
    
    // Toggle state for barcode/QR generator
    let showCodeGenerator = false;
    
    // Close drawer
    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<!-- Permission Details Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Permission Details
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    
    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedPermission}
            <!-- Permission Details -->
            <div class="space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                        <span class="text-sm font-medium word-break">#{selectedPermission.id}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                        <span class="text-sm font-medium text-mono word-break">{selectedPermission.name}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">API Support:</span>
                        <div class="flex items-center gap-2">
                            <input 
                                class="kt-switch" 
                                type="checkbox" 
                                checked={selectedPermission.is_api}
                                disabled
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">Web Support:</span>
                        <div class="flex items-center gap-2">
                            <input 
                                class="kt-switch" 
                                type="checkbox" 
                                checked={selectedPermission.is_web}
                                disabled
                            />
                        </div>
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Created:</span>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{formatTimeStamp(selectedPermission.created_at)}</span>
                        </div>
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{formatTimeStamp(selectedPermission.updated_at)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-border w-full"></div>

                <!-- Toggle Button for Code Generator -->
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
                
                <!-- Barcode & QR Code Generator (Conditional) -->
                {#if showCodeGenerator}
                    <BarcodeQRGenerator 
                        value={selectedPermission.id.toString()} 
                        title="Permission ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {:else}
            <!-- Empty state -->
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-shield-halved text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No Permission Selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select a permission to view its details.
                </p>
            </div>
        {/if}
    </div>
</div>