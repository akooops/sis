<!-- resources/js/Pages/Shared/Utils/Scanner.svelte -->
<script>
    import { createEventDispatcher, onMount } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let value = '';
    export let scannerIcon = 'fa-solid fa-qrcode';
    export let showScanner = true;
    export let disabled = false;
    export let continuousScan = false; // New prop for continuous scanning

    // Local state
    let isScanning = false;
    let scanBuffer = '';
    let searchInput;
    
    // Scanner modal state
    let scannerType = 'USB-HID'; // Default to USB-HID
    
    // USB-COM Serial state
    let serialPort = null;
    let serialReader = null;
    let isSerialConnected = false;
    
    // WebNFC state
    let isNFCScanning = false;
    let nfcReader = null;

    // Handle scanner click
    function handleScannerClick(event) {
        if (isScanning) {
            // Stop scanning if already active
            stopScanning();
            return;
        }
        
        // Show modal to choose scanner type
        const toggleButton = document.querySelector('[data-kt-modal-toggle="#scanner_modal"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }
    
    function startScanning() {
        isScanning = true;
        scanBuffer = ''; // Reset buffer
        value = ''; // Clear current value
        
        // Emit started scan event
        dispatch('startedScan');
        
        if (scannerType === 'USB-HID') {
            startHIDScanning();
        } else if (scannerType === 'USB-COM') {
            startSerialScanning();
        } else if (scannerType === 'NFC') {
            startNFCScanning();
        }
    }
    
    function startHIDScanning() {
        // Focus on the hidden input field
        if (searchInput) {
            searchInput.focus();
        }
        
        // Add event listener for scan input
        document.addEventListener('keydown', handleScanning);
    }
    
    async function startSerialScanning() {
        try {
            // Check if Web Serial API is supported
            if (!('serial' in navigator)) {
                alert('Web Serial API is not supported in this browser. Please use Chrome or Edge.');
                stopScanning();
                return;
            }

            // Check if already connected to a serial port
            if (isSerialConnected && serialPort) {
                console.log('Already connected to serial port, reusing connection');
                startSerialReading();
                return;
            }

            // Clean up any existing connection
            if (serialPort) {
                try {
                    await serialPort.close();
                } catch (e) {
                    console.warn('Error closing existing port:', e);
                }
                serialPort = null;
                isSerialConnected = false;
            }

            // Request port access
            serialPort = await navigator.serial.requestPort();
            
            // Serial port settings
            const settings = {
                baudRate: 9600,
                dataBits: 8,
                stopBits: 1,
                parity: "none",
                flowControl: "none"
            };
            
            // Open the port
            await serialPort.open(settings);
            isSerialConnected = true;
            
            console.log('Connected to serial barcode scanner');
            
            // Start reading data
            startSerialReading();
            
        } catch (error) {
            console.error('Serial connection failed:', error);
            alert(`Serial connection failed: ${error.message}`);
            stopScanning();
        }
    }
    
    async function startNFCScanning() {
        try {
            // Check if WebNFC is supported
            if (!('NDEFReader' in window)) {
                alert('WebNFC is not supported in this browser. Please use Chrome on Android.');
                stopScanning();
                return;
            }

            // Check if NFC is available
            if (!navigator.nfc) {
                alert('NFC is not available on this device.');
                stopScanning();
                return;
            }

            isNFCScanning = true;
            nfcReader = new NDEFReader();
            
            // Request NFC permission
            await nfcReader.scan();
            
            console.log('NFC scanning started. Hold an NFC tag near the device.');
            
            // Listen for NFC tag reads
            nfcReader.addEventListener('reading', handleNFCReading);
            nfcReader.addEventListener('readingerror', handleNFCError);
            
        } catch (error) {
            console.error('NFC scanning failed:', error);
            alert(`NFC scanning failed: ${error.message}`);
            stopScanning();
        }
    }
    
    function handleNFCReading(event) {
        console.log('NFC tag detected:', event);
        
        // Read the NDEF message
        const message = event.message;
        let nfcData = '';
        
        // Process NDEF records
        for (const record of message.records) {
            if (record.recordType === 'text') {
                const textDecoder = new TextDecoder(record.encoding);
                nfcData = textDecoder.decode(record.data);
            } else if (record.recordType === 'url') {
                const urlDecoder = new TextDecoder();
                nfcData = urlDecoder.decode(record.data);
            } else if (record.recordType === 'mime' && record.mediaType === 'text/plain') {
                const textDecoder = new TextDecoder();
                nfcData = textDecoder.decode(record.data);
            } else {
                // For other record types, try to decode as text
                try {
                    const textDecoder = new TextDecoder();
                    nfcData = textDecoder.decode(record.data);
                } catch (e) {
                    console.warn('Could not decode NFC record:', record);
                    nfcData = `NFC Tag ID: ${event.serialNumber || 'Unknown'}`;
                }
            }
            
            // If we found data, break
            if (nfcData) break;
        }
        
        // If no readable data found, use the serial number
        if (!nfcData) {
            nfcData = event.serialNumber || 'NFC Tag Detected';
        }
        
        console.log('NFC data read:', nfcData);
        handleScannedData(nfcData);
    }
    
    function handleNFCError(event) {
        console.error('NFC reading error:', event);
        // Don't stop scanning on error, just log it
    }
    
    async function startSerialReading() {
        try {
            while (serialPort && serialPort.readable && isScanning) {
                const textDecoder = new TextDecoderStream();
                const readableStreamClosed = serialPort.readable.pipeTo(textDecoder.writable);
                
                serialReader = textDecoder.readable
                    .pipeThrough(new TransformStream(new LineBreakTransformer()))
                    .getReader();

                try {
                    while (isScanning) {
                        const { value: data, done } = await serialReader.read();
                        if (done) break;
                        
                        if (data && data.trim()) {
                            const cleanData = data.trim();
                            console.log('Serial scanned data:', cleanData);
                            handleScannedData(cleanData);
                        }
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Serial reading error:', error);
                    }
                } finally {
                    if (serialReader) {
                        serialReader.releaseLock();
                    }
                }
                
                await readableStreamClosed.catch(() => {}); // Ignore the error
            }
        } catch (error) {
            console.error('Serial start reading error:', error);
        }
    }
    
    function handleScannedData(data) {
        // Update the value
        value = data;
        
        // Emit scanned event
        dispatch('scanned', { data });
        
        // Stop scanning only if not in continuous mode
        if (!continuousScan) {
            stopScanning();
        } else {
            // Reset buffer for next scan in continuous mode
            scanBuffer = '';
            value = '';
        }
    }
    
    async function stopScanning() {
        isScanning = false;
        document.removeEventListener('keydown', handleScanning);
        
        // Emit stopped scan event
        dispatch('stoppedScan');
        
        // Stop NFC scanning
        if (isNFCScanning && nfcReader) {
            try {
                // Remove event listeners
                nfcReader.removeEventListener('reading', handleNFCReading);
                nfcReader.removeEventListener('readingerror', handleNFCError);
                isNFCScanning = false;
                nfcReader = null;
                console.log('NFC scanning stopped');
            } catch (error) {
                console.error('Error stopping NFC scanning:', error);
            }
        }
        
        // Close serial connection if active
        if (isSerialConnected && serialPort) {
            try {
                // First, cancel and release the reader
                if (serialReader) {
                    try {
                        await serialReader.cancel();
                    } catch (e) {
                        console.warn('Error canceling reader:', e);
                    }
                    
                    try {
                        serialReader.releaseLock();
                    } catch (e) {
                        console.warn('Error releasing reader lock:', e);
                    }
                    
                    serialReader = null;
                }
                
                // Wait a bit to ensure the stream is properly released
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Then close the port
                if (serialPort && serialPort.readable) {
                    await serialPort.close();
                    console.log('Serial connection closed');
                }
                
                serialPort = null;
                isSerialConnected = false;
                
            } catch (error) {
                console.error('Error closing serial connection:', error);
                // Force reset the state even if close fails
                serialPort = null;
                isSerialConnected = false;
                serialReader = null;
            }
        }
        
        console.log('Stopped scanning');
    }
    
    // Modal functions
    function confirmScannerType() {
        // Close modal using KT modal dismiss
        const dismissButton = document.querySelector('[data-kt-modal-dismiss="#scanner_modal"]');
        if (dismissButton) {
            dismissButton.click();
        }
        startScanning();
    }
    
    function cancelScannerModal() {
        // Close modal using KT modal dismiss
        const dismissButton = document.querySelector('[data-kt-modal-dismiss="#scanner_modal"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }

    function handleScanning(event) {        
        // Check for barcode scanner end characters (Enter, Tab, or other special chars)
        if (event.key === 'Enter' || event.key === 'Tab' || event.keyCode === 13 || event.keyCode === 9) {
            event.preventDefault();

            if (scanBuffer.length > 0) {
                // Update the value
                value = scanBuffer;
                
                // Emit scanned event
                dispatch('scanned', { data: scanBuffer });
                
                // Stop scanning only if not in continuous mode
                if (!continuousScan) {
                    stopScanning();
                } else {
                    // Reset buffer for next scan in continuous mode
                    scanBuffer = '';
                    value = '';
                }
            }
            return;
        }
        
        // Add character to buffer (ignore special keys)
        if (event.key.length === 1) {
            scanBuffer += event.key;
        }
    }

    // Line break transformer for serial data
    class LineBreakTransformer {
        constructor() {
            this.chunks = "";
        }

        transform(chunk, controller) {
            this.chunks += chunk;
            const lines = this.chunks.split(/\r\n|\r|\n/);
            this.chunks = lines.pop() || "";
            lines.forEach((line) => {
                if (line.length > 0) {
                    controller.enqueue(line);
                }
            });
        }

        flush(controller) {
            if (this.chunks.length > 0) {
                controller.enqueue(this.chunks);
            }
        }
    }

    // Cleanup on component destroy
    onMount(() => {
        return () => {
            // Cleanup connections on component destroy
            if (isSerialConnected) {
                stopScanning();
            }
            if (isNFCScanning) {
                stopScanning();
            }
        };
    });
</script>

<!-- Hidden input for HID scanning -->
<input 
    bind:this={searchInput}
    type="text" 
    style="position: absolute; left: -9999px; opacity: 0;"
    tabindex="-1"
/>

<div class="flex items-center gap-1">
    {#if showScanner}
        <div class="flex items-center gap-1">
            <button 
                type="button"
                class="kt-btn kt-btn-sm"
                class:kt-btn-ghost={!isScanning}
                class:kt-btn-outline-primary={isScanning}
                on:click={handleScannerClick}
                disabled={disabled}
                title="Scan barcode/QR code/NFC tag"
            >
                <i class="{scannerIcon}"></i>
            </button>
        </div>
    {/if}
</div>

<!-- Hidden button to trigger modal -->
<button type="button" style="display:none" data-kt-modal-toggle="#scanner_modal"></button>

<!-- Scanner Type Selection Modal -->
<div class="kt-modal" data-kt-modal="true" id="scanner_modal">
    <div class="kt-modal-content max-w-[500px] top-[5%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Select Scanner Type</h3>
            <button
                type="button"
                class="kt-modal-close"
                aria-label="Close modal"
                data-kt-modal-dismiss="#scanner_modal"
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
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Choose how your scanner connects to your device:
                </p>
                
                <!-- Scanner Type Options -->
                <div class="space-y-3">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" class:border-primary={scannerType === 'USB-HID'} class:bg-primary-50={scannerType === 'USB-HID'}>
                        <input 
                            type="radio" 
                            bind:group={scannerType} 
                            value="USB-HID" 
                            class="mr-1"
                        />
                        <div class="flex-1">
                            <div class="font-medium text-mono mb-1">USB-HID (Keyboard Mode)</div>
                            <div class="text-sm text-muted-foreground">
                                Scanner acts like a keyboard and types the barcode data directly into the input field
                            </div>
                        </div>
                        <i class="fa-solid fa-keyboard text-2xl text-muted-foreground"></i>
                    </label>
                    
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" class:border-primary={scannerType === 'USB-COM'} class:bg-primary-50={scannerType === 'USB-COM'}>
                        <input 
                            type="radio" 
                            bind:group={scannerType} 
                            value="USB-COM" 
                            class="mr-1"
                        />
                        <div class="flex-1">
                            <div class="font-medium text-mono mb-1">USB-COM (Serial Port)</div>
                            <div class="text-sm text-muted-foreground">
                                Scanner connects via serial port communication
                            </div>
                        </div>
                        <i class="fa-solid fa-plug text-2xl text-muted-foreground"></i>
                    </label>
                    
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" class:border-primary={scannerType === 'NFC'} class:bg-primary-50={scannerType === 'NFC'}>
                        <input 
                            type="radio" 
                            bind:group={scannerType} 
                            value="NFC" 
                            class="mr-1"
                        />
                        <div class="flex-1">
                            <div class="font-medium text-mono mb-1">NFC (Near Field Communication)</div>
                            <div class="text-sm text-muted-foreground">
                                Read NFC tags using WebNFC API (requires Chrome on Android)
                            </div>
                        </div>
                        <i class="fa-solid fa-wifi text-2xl text-muted-foreground"></i>
                    </label>
                </div>
            </div>
        </div>
        <div class="kt-modal-footer">
            <div></div>
            <div class="flex gap-4">
                <button
                    type="button"
                    class="kt-btn kt-btn-ghost"
                    data-kt-modal-dismiss="#scanner_modal"
                    on:click={cancelScannerModal}
                >
                    Cancel
                </button>
                <button 
                    type="button"
                    class="kt-btn kt-btn-primary"
                    on:click={confirmScannerType}
                >
                    <i class="fa-solid fa-qrcode mr-2"></i>
                    Start Scanning
                </button>
            </div>
        </div>
    </div>
</div>