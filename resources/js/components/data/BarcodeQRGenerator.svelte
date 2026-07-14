<script>
    /**
     * BarcodeQRGenerator — renders a CODE128 barcode + a QR code for `value`
     * (typically a record id), each with download (PNG) and print actions.
     * Uses the npm `jsbarcode` + `qrcode` packages (no window globals / no jQuery).
     */
    import { onMount } from 'svelte';
    import JsBarcode from 'jsbarcode';
    import QRCode from 'qrcode';
    import { t } from '@/lib/i18n';

    let { value = '' } = $props();

    let barcodeCanvas;
    let qrCanvas;
    let mounted = $state(false);

    async function render() {
        if (!mounted || !value || !barcodeCanvas || !qrCanvas) return;
        try {
            JsBarcode(barcodeCanvas, String(value), {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 12,
                margin: 5,
                background: '#ffffff',
                lineColor: '#000000',
            });
        } catch {
            /* value not encodable — leave blank */
        }
        try {
            await QRCode.toCanvas(qrCanvas, String(value), { width: 96, margin: 1 });
        } catch {
            /* ignore */
        }
    }

    onMount(() => {
        mounted = true;
    });

    // Re-render whenever the value changes.
    $effect(() => {
        void value;
        if (mounted) render();
    });

    function download(canvas, name) {
        if (!canvas) return;
        const link = document.createElement('a');
        link.download = `${name}-${value}.png`;
        link.href = canvas.toDataURL();
        link.click();
    }

    function print(canvas, label) {
        if (!canvas) return;
        const dataUrl = canvas.toDataURL();
        const win = window.open('', '_blank');
        if (!win) return;
        win.document.write(
            `<html><head><title>${label} — ${value}</title>` +
                `<style>body{margin:0;padding:20px;text-align:center;font-family:Arial,sans-serif}img{max-width:100%}.info{margin-bottom:20px;font-size:14px;color:#666}</style>` +
                `</head><body><div class="info">${label}: ${value}</div><img src="${dataUrl}" alt="${label}"/></body></html>`,
        );
        win.document.close();
        win.focus();
        win.print();
        win.close();
    }
</script>

<div class="kt-card">
    <div class="kt-card-body p-4">
        {#if value}
            <div class="grid grid-cols-1 gap-4">
                <!-- Barcode -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-mono flex items-center gap-2">
                            <i class="ki-filled ki-barcode text-primary"></i>{$t('common.detail.barcode')}
                        </h5>
                        <div class="flex gap-1">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => download(barcodeCanvas, 'barcode')} title={$t('common.actions.download')}>
                                <i class="ki-filled ki-exit-down"></i>
                            </button>
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => print(barcodeCanvas, $t('common.detail.barcode'))} title={$t('common.detail.print')}>
                                <i class="ki-filled ki-printer"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-center items-center p-3 bg-white border border-border rounded-lg min-h-[75px]">
                        <canvas bind:this={barcodeCanvas} class="max-w-full"></canvas>
                    </div>
                </div>

                <!-- QR code -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-mono flex items-center gap-2">
                            <i class="ki-filled ki-scan-barcode text-primary"></i>{$t('common.detail.qr_code')}
                        </h5>
                        <div class="flex gap-1">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => download(qrCanvas, 'qrcode')} title={$t('common.actions.download')}>
                                <i class="ki-filled ki-exit-down"></i>
                            </button>
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => print(qrCanvas, $t('common.detail.qr_code'))} title={$t('common.detail.print')}>
                                <i class="ki-filled ki-printer"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-center items-center p-3 bg-white border border-border rounded-lg min-h-[75px]">
                        <canvas bind:this={qrCanvas}></canvas>
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>
