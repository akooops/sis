<script>
    import BarcodeQRGenerator from '../../Shared/Utils/BarcodeQRGenerator.svelte';
    
    export let selectedSupplier = null;
    
    let showCodeGenerator = false;
    
    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<!-- Supplier Details Drawer -->
<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="view_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Supplier Details
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    
    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if selectedSupplier}
            <div class="space-y-4">
                <div class="space-y-3">
                    <img 
                        src={selectedSupplier.logo_url} 
                        alt={selectedSupplier.name}
                        class="w-[100px] h-[100px] rounded-lg object-cover"
                    />

                    <div class="border-b border-border w-full"></div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">ID:</span>
                        <span class="text-sm font-medium word-break">#{selectedSupplier.id}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                        <span class="text-sm font-medium text-mono word-break">{selectedSupplier.name}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Code:</span>
                        {#if selectedSupplier.code}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.code}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Email:</span>
                        <span class="text-sm font-medium text-mono word-break">{selectedSupplier.email}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Phone:</span>
                        {#if selectedSupplier.phone}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.phone}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Website:</span>
                        {#if selectedSupplier.website}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.website}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Address:</span>
                        {#if selectedSupplier.address}
                            <span class="text-sm font-medium text-mono word-break text-end">{selectedSupplier.address}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">City:</span>
                        {#if selectedSupplier.city}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.city}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">State:</span>
                        {#if selectedSupplier.state}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.state}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Zip:</span>
                        {#if selectedSupplier.zip}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.zip}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Country:</span>
                        {#if selectedSupplier.country}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.country}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-mono">Contact Person</span>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Name:</span>
                        {#if selectedSupplier.contact_person}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Email:</span>
                        {#if selectedSupplier.contact_person_email}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_email}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Phone:</span>
                        {#if selectedSupplier.contact_person_phone}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_phone}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Position:</span>
                        {#if selectedSupplier.contact_person_position}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_position}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="border-b border-border w-full"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Department:</span>
                        {#if selectedSupplier.contact_person_department}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_department}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Dept. Email:</span>
                        {#if selectedSupplier.contact_person_department_email}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_department_email}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Dept. Phone:</span>
                        {#if selectedSupplier.contact_person_department_phone}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_department_phone}</span>
                        {:else}
                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">
                                N/A
                            </span>
                        {/if}
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground min-w-20">Dept. Position:</span>
                        {#if selectedSupplier.contact_person_department_position}
                            <span class="text-sm font-medium text-mono word-break">{selectedSupplier.contact_person_department_position}</span>
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
                        <span class="text-sm font-medium">{formatTimeStamp(selectedSupplier.created_at)}</span>
                    </div>
                </div>

                <div class="flex items-start justify-between">
                    <span class="text-sm font-medium text-muted-foreground min-w-20">Updated:</span>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{formatTimeStamp(selectedSupplier.updated_at)}</span>
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
                        value={selectedSupplier.id.toString()} 
                        title="Supplier ID Codes"
                        showTitle={true}
                    />
                {/if}
            </div>
        {:else}
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-truck-field text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No Supplier Selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select a supplier to view its details.
                </p>
            </div>
        {/if}
    </div>
</div>
