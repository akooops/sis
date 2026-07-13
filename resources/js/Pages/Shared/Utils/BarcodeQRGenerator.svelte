<script>
    import { onMount } from 'svelte';
    
    export let value = '';
    
    let barcodeCanvas;
    let qrCanvas;
    let mounted = false;
    
    // Generate barcode using JsBarcode
    function generateBarcode() {
        if (!mounted || !barcodeCanvas || !value) return;
        
        try {
            if (window.JsBarcode) {
                window.JsBarcode(barcodeCanvas, value, {
                    format: "CODE128",
                    width: 1.5,
                    height: 40,
                    displayValue: true,
                    fontSize: 10,
                    margin: 5,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
            } else {
                console.error('JsBarcode library not found');
            }
        } catch (error) {
            console.error('Error generating barcode:', error);
        }
    }
    
    // Generate QR code using QRCode.js
    function generateQRCode() {
        if (!mounted || !qrCanvas || !value) return;
        
        try {
            if (window.QRCode) {      
                // Clear the container
                qrCanvas.innerHTML = '';

                const qrcode = new window.QRCode(qrCanvas, {
                    text: value,
                    width: 60,
                    height: 60,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: window.QRCode.CorrectLevel.M
                });
            } else {
                console.error('QRCode library not found');
            }
        } catch (error) {
            console.error('Error generating QR code:', error);
        }
    }
    
    // Download barcode as PNG
    function downloadBarcode() {
        if (!barcodeCanvas) return;
        
        const link = document.createElement('a');
        link.download = `barcode-${value}.png`;
        link.href = barcodeCanvas.toDataURL();
        link.click();
    }
    
    // Download QR code as PNG
    function downloadQRCode() {
        if (!qrCanvas) return;
        
        try {
            // Find the canvas element inside the QR code container
            const canvas = qrCanvas.querySelector('canvas');
            if (canvas && typeof canvas.toDataURL === 'function') {
                const link = document.createElement('a');
                link.download = `qrcode-${value}.png`;
                link.href = canvas.toDataURL();
                link.click();
            } else {
                console.error('QR Code canvas not found for download');
            }
        } catch (error) {
            console.error('Error downloading QR code:', error);
        }
    }
    
    // Print barcode
    function printBarcode() {
        if (!barcodeCanvas) return;
        
        try {
            const dataUrl = barcodeCanvas.toDataURL();
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print Barcode - ${value}</title>
                        <style>
                            body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; }
                            img { max-width: 100%; height: auto; }
                            .info { margin-bottom: 20px; font-size: 14px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="info">Barcode: ${value}</div>
                        <img src="${dataUrl}" alt="Barcode for ${value}" />
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        } catch (error) {
            console.error('Error printing barcode:', error);
        }
    }
    
    // Print QR code
    function printQRCode() {
        if (!qrCanvas) return;
        
        try {
            const canvas = qrCanvas.querySelector('canvas');
            if (canvas && typeof canvas.toDataURL === 'function') {
                const dataUrl = canvas.toDataURL();
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print QR Code - ${value}</title>
                            <style>
                                body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; }
                                img { max-width: 100%; height: auto; }
                                .info { margin-bottom: 20px; font-size: 14px; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class="info">QR Code: ${value}</div>
                            <img src="${dataUrl}" alt="QR Code for ${value}" />
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            } else {
                console.error('QR Code canvas not found for printing');
            }
        } catch (error) {
            console.error('Error printing QR code:', error);
        }
    }
    
    
    onMount(() => {
        mounted = true;
    });
    
    // Regenerate when value changes or component mounts
    $: if (mounted && value) {
        generateBarcode();
        generateQRCode();
    }
</script>

<div class="kt-card">
    <div class="kt-card-body p-4">
        {#if value}
            <!-- Code Generators -->
            <div class="grid grid-cols-1 gap-4">
                <!-- Barcode Section -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-mono flex items-center gap-2">
                            <i class="fa-solid fa-barcode text-primary"></i>
                            Barcode
                        </h5>
                        <div class="flex gap-1">
                            <button 
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={downloadBarcode}
                                title="Download barcode"
                            >
                                <i class="fa-solid fa-download"></i>
                            </button>
                            <button 
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={printBarcode}
                                title="Print barcode"
                            >
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-center items-center p-3 bg-white border rounded-lg h-[75px]">
                        <canvas bind:this={barcodeCanvas} class="max-w-full max-h-full"></canvas>
                    </div>
                </div>
                
                <!-- QR Code Section -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-mono flex items-center gap-2">
                            <i class="fa-solid fa-qrcode text-primary"></i>
                            QR Code
                        </h5>
                        <div class="flex gap-1">
                            <button 
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={downloadQRCode}
                                title="Download QR code"
                            >
                                <i class="fa-solid fa-download"></i>
                            </button>
                            <button 
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={printQRCode}
                                title="Print QR code"
                            >
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-center items-center p-3 bg-white border rounded-lg h-[75px]">
                        <div bind:this={qrCanvas} class="max-w-full max-h-full"></div>
                    </div>
                </div>
            </div>
        {:else}
            <!-- Empty state -->
            <div class="flex flex-col items-center justify-center text-center py-8">
                <div class="mb-3">
                    <i class="fa-solid fa-qrcode text-3xl text-muted-foreground"></i>
                </div>
                <h5 class="text-sm font-semibold text-mono mb-1">No Value Provided</h5>
                <p class="text-xs text-muted-foreground">
                    Provide a value to generate barcode and QR code
                </p>
            </div>
        {/if}
    </div>
</div>