<script>
    import { createEventDispatcher, onMount } from 'svelte';
    import Scanner from '../Scanner.svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let value = '';
    export let placeholder = 'Search...';
    export let debounceMs = 500;
    export let showScanner = true;
    export let scannerIcon = 'fa-solid fa-qrcode';
    export let searchIcon = 'fa-solid fa-magnifying-glass';

    // Local state
    let searchInput;
    let searchTimeout;
    let isScanning = false;

    // Handle search input change with debouncing
    function handleSearchInput(event) {
        value = event.target.value;
        
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for debouncing
        searchTimeout = setTimeout(() => {
            dispatch('search', { value });
        }, debounceMs);
    }

    // Handle scanner events
    function handleScannerStarted() {
        isScanning = true;
    }

    function handleScannerStopped() {
        isScanning = false;
    }

    function handleScanned(event) {

        value = event.detail.data;
        if (searchInput) {
            searchInput.value = event.detail.data;
        }
        // Trigger search immediately for scanned input
        dispatch('search', { value: event.detail.data });
    }

    // Handle Enter key
    function handleKeydown(event) {
        if (event.key === 'Enter') {
            dispatch('search', { value });
        }
    }

    // Cleanup on component destroy
    onMount(() => {
        return () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        };
    });
</script>

<div class="flex items-center gap-1">
    <label class="kt-input kt-input-sm min-w-12" class:hidden={isScanning}>
        <i class="{searchIcon}"></i> 
        <input 
            bind:this={searchInput}
            type="text" 
            {placeholder}
            {value}
            on:input={handleSearchInput}
            on:keydown={handleKeydown}>
    </label>
    
    {#if isScanning}
        <span class="text-sm text-primary">
            Scanning...
        </span>
    {/if}

    {#if showScanner}
        <Scanner
            bind:value={value}
            {scannerIcon}
            on:scanned={handleScanned}
            on:startedScan={handleScannerStarted}
            on:stoppedScan={handleScannerStopped}
        />
    {/if}
</div>