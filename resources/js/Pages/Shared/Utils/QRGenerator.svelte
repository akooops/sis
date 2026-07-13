<script>
    import { onMount } from 'svelte';
    
    export let value = '';
    export let size = 60; // QR code size
    
    let qrCanvas;
    let mounted = false;
    
    // Generate QR code using QRCode.js
    function generateQRCode() {
        if (!mounted || !qrCanvas || !value) return;
        
        try {
            if (window.QRCode) {      
                // Clear the container
                qrCanvas.innerHTML = '';

                const qrcode = new window.QRCode(qrCanvas, {
                    text: value,
                    width: size,
                    height: size,
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
        generateQRCode();
    }
</script>

<div class="flex items-center gap-2">
    {#if value}
        <!-- Action Buttons -->
        <div class="flex flex-col gap-1">
            <button 
                type="button"
                class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost"
                on:click={downloadQRCode}
                title="Download QR code"
            >
                <i class="fa-solid fa-download text-xs"></i>
            </button>
            <button 
                type="button"
                class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost"
                on:click={printQRCode}
                title="Print QR code"
            >
                <i class="fa-solid fa-print text-xs"></i>
            </button>
        </div>
        
        <!-- QR Code -->
        <div class="flex justify-center items-center p-2 bg-white border rounded">
            <div bind:this={qrCanvas} class="flex items-center justify-center" style="width: {size}px; height: {size}px;"></div>
        </div>
    {:else}
        <!-- Empty state -->
        <div class="flex items-center justify-center p-2 text-muted-foreground">
            <i class="fa-solid fa-qrcode text-lg"></i>
        </div>
    {/if}
</div>

