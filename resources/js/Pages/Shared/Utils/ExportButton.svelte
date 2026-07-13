<!-- ExportButton.svelte -->
<script>
    import { createEventDispatcher } from 'svelte';
    
    const dispatch = createEventDispatcher();
    
    // Props
    export let tableData = [];
    export let headers = [];
    export let filename = 'export';
    export let totalRecords = 0;
    export let currentPerPage = 10;
    
    // Backend export options (optional)
    export let backendExport = null;
    export let filters = {};
    
    // Export state
    let exporting = false;
    let exportFormat = 'csv'; // 'csv', 'pdf'
    let exportMethod = 'frontend'; // 'frontend' or 'backend'
    
    // Auto-suggest backend export for large datasets
    $: if (backendExport?.enabled && totalRecords > (backendExport.maxRecords || 5000)) {
        exportMethod = 'backend';
    }
    
    // Handle export button click
    function handleExportClick() {
        // Always show modal to choose export options
        const toggleButton = document.querySelector('[data-kt-modal-toggle="#export_modal"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    // Confirm export options from modal
    function confirmExportOptions() {
        // Close modal
        const dismissButton = document.querySelector('[data-kt-modal-dismiss="#export_modal"]');
        if (dismissButton) {
            dismissButton.click();
        }
        
        startExport();
    }
    
    // Cancel export modal
    function cancelExportModal() {
        const dismissButton = document.querySelector('[data-kt-modal-dismiss="#export_modal"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
    
    // Start the actual export
    async function startExport() {
        exporting = true;
        
        try {
            if (exportMethod === 'backend' && backendExport?.enabled) {
                await exportBackend();
            } else {
                exportFrontend();
            }
        } catch (error) {
            toast('Export failed: ' + error.message, 'error');
        } finally {
            exporting = false;
        }
    }
    
    // Frontend export
    function exportFrontend() {
        if (!tableData.length) {
            toast('No data to export', 'warning');
            return;
        }
        
        switch (exportFormat) {
            case 'csv':
                exportCSV();
                break;
            case 'pdf':
                exportPDF();
                break;
        }
    }
    
    // Backend export
    async function exportBackend() {
        const params = new URLSearchParams({
            ...filters,
            export: exportFormat,
            format: 'download'
        });
        
        const response = await fetch(`${backendExport.endpoint}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        });
        
        if (!response.ok) throw new Error('Export failed');
        
        // Handle file download
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${filename}-${new Date().toISOString().split('T')[0]}.${exportFormat}`;
        a.click();
        URL.revokeObjectURL(url);
        
        toast(`Export completed (${totalRecords} records)`, 'success');
    }
    
    // CSV Export
    function exportCSV() {
        const csvContent = [
            headers.map(h => h.label || h).join(','),
            ...tableData.map(row => 
                headers.map(header => {
                    let value = getNestedValue(row, header.key || header);
                    if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                        value = `"${value.replace(/"/g, '""')}"`;
                    }
                    return value || '';
                }).join(',')
            )
        ].join('\n');
        
        downloadFile(csvContent, 'text/csv', 'csv');
        toast(`Exported ${tableData.length} records as CSV`, 'success');
    }
    
    // PDF Export (simple table format)
    function exportPDF() {
        // For a more advanced PDF, you'd want to use jsPDF or similar
        // This is a basic implementation
        const htmlContent = `
            <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 12px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .header { text-align: center; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>${filename.toUpperCase()} Export</h2>
                        <p>Generated on ${new Date().toLocaleString()}</p>
                        <p>Total Records: ${tableData.length}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>${headers.map(h => `<th>${h.label || h}</th>`).join('')}</tr>
                        </thead>
                        <tbody>
                            ${tableData.map(row => 
                                `<tr>${headers.map(header => 
                                    `<td>${getNestedValue(row, header.key || header) || ''}</td>`
                                ).join('')}</tr>`
                            ).join('')}
                        </tbody>
                    </table>
                </body>
            </html>
        `;
        
        // Open in new window for printing/saving as PDF
        const printWindow = window.open('', '_blank');
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        printWindow.focus();
        
        // Auto-trigger print dialog
        setTimeout(() => {
            printWindow.print();
        }, 250);
        
        toast(`PDF export opened in new window (${tableData.length} records)`, 'success');
    }
    
    // Helper functions
    function downloadFile(content, mimeType, extension) {
        const blob = new Blob([content], { type: `${mimeType};charset=utf-8;` });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${filename}-${new Date().toISOString().split('T')[0]}.${extension}`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    function getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }
</script>

<!-- Export Button -->
<div class="flex items-center gap-2">
    <button 
        type="button"
        class="kt-btn kt-btn-sm kt-btn-ghost"
        class:kt-btn-outline-primary={exporting}
        on:click={handleExportClick}
        disabled={exporting || (!tableData.length && exportMethod === 'frontend')}
        title="Export data"
    >
        {#if exporting}
            <i class="fa-solid fa-spinner fa-spin mr-1"></i>
        {:else}
            <i class="fa-solid fa-download mr-1"></i>
        {/if}
    </button>
</div>

<!-- Hidden button to trigger modal -->
<button style="display:none" data-kt-modal-toggle="#export_modal"></button>

<!-- Export Options Modal -->
<div class="kt-modal" data-kt-modal="true" id="export_modal">
    <div class="kt-modal-content max-w-[600px] top-[5%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Export Options</h3>
            <button
                type="button"
                class="kt-modal-close"
                aria-label="Close modal"
                data-kt-modal-dismiss="#export_modal"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="lucide lucide-x"
                    aria-hidden="true"
                >
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="kt-modal-body">
            <div class="space-y-6">
                <!-- Notice -->
                <div class="kt-card kt-card-bordered">
                    <div class="kt-card-body p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-info-circle text-primary text-lg mt-0.5"></i>
                            <div>
                                <h4 class="font-medium text-mono mb-1">Export Notice</h4>
                                <p class="text-sm text-muted-foreground">
                                    The export will include the same data currently displayed in the table based on your selected 
                                    <strong>per page setting ({currentPerPage} {currentPerPage === 'All' || currentPerPage > 1000 ? 'records' : 'per page'})</strong> 
                                    and applied filters. To export more data, increase the "per page" value before exporting.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Export Format Options -->
                <div class="space-y-5 mt-4">
                    <h4 class="text-sm font-semibold text-mono">Choose Export Format:</h4>
                    
                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                               class:border-primary={exportFormat === 'csv'}>
                            <input 
                                type="radio" 
                                bind:group={exportFormat} 
                                value="csv" 
                                class="mr-1"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-mono mb-1">CSV (Comma Separated Values)</div>
                                <div class="text-sm text-muted-foreground">
                                    Best for spreadsheet applications and data analysis. Small file size.
                                </div>
                            </div>
                            <i class="fa-solid fa-file-csv text-2xl text-green-600"></i>
                        </label>
                        
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                               class:border-primary={exportFormat === 'pdf'}>
                            <input 
                                type="radio" 
                                bind:group={exportFormat} 
                                value="pdf" 
                                class="mr-1"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-mono mb-1">PDF (Portable Document)</div>
                                <div class="text-sm text-muted-foreground">
                                    Best for reports and sharing. Opens print dialog for saving as PDF.
                                </div>
                            </div>
                            <i class="fa-solid fa-file-pdf text-2xl text-destructive"></i>
                        </label>
                    </div>
                </div>
                
                <!-- Export Method (if backend is available) -->
                {#if backendExport?.enabled}
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-mono">Export Method:</h4>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                                   class:border-primary={exportMethod === 'frontend'} 
                                   class:bg-primary-50={exportMethod === 'frontend'}>
                                <input 
                                    type="radio" 
                                    bind:group={exportMethod} 
                                    value="frontend" 
                                    class="mr-3"
                                />
                                <div class="flex-1">
                                    <div class="font-medium text-mono">Current View ({tableData.length} records)</div>
                                    <div class="text-xs text-muted-foreground">Export only the data currently loaded in the table</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                                   class:border-primary={exportMethod === 'backend'} 
                                   class:bg-primary-50={exportMethod === 'backend'}>
                                <input 
                                    type="radio" 
                                    bind:group={exportMethod} 
                                    value="backend" 
                                    class="mr-3"
                                />
                                <div class="flex-1">
                                    <div class="font-medium text-mono">All Filtered Data ({totalRecords} records)</div>
                                    <div class="text-xs text-muted-foreground">Export all data matching your current filters</div>
                                </div>
                            </label>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
        <div class="kt-modal-footer">
            <div></div>
            <div class="flex gap-4">
                <button
                    class="kt-btn kt-btn-ghost"
                    data-kt-modal-dismiss="#export_modal"
                    on:click={cancelExportModal}
                >
                    Cancel
                </button>
                <button 
                    class="kt-btn kt-btn-primary"
                    on:click={confirmExportOptions}
                >
                    <i class="fa-solid fa-download mr-2"></i>
                    Export {exportFormat.toUpperCase()}
                </button>
            </div>
        </div>
    </div>
</div>