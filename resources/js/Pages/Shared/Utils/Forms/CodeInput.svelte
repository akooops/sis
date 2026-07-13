<!-- CodeInput.svelte -->
<script>
    import { createEventDispatcher } from 'svelte';
    
    const dispatch = createEventDispatcher();
    
    // Props
    export let value = '';
    export let label = 'Code';
    export let placeholder = 'Enter code or leave blank to auto-generate';
    export let required = false;
    export let disabled = false;
    export let error = null;
    export let generateFrom = []; // Array of values to generate code from
    export let maxLength = 255;
    export let separator = '-';
    
    // Internal state
    let manuallyEdited = false;
    let suggestedCode = '';
    
    // Watch for changes in generateFrom array
    $: if (!manuallyEdited && generateFrom.length > 0) {
        suggestedCode = generateCode(generateFrom);
        if (value !== suggestedCode) {
            value = suggestedCode;
            dispatch('input', { value });
        }
    }
    
    // Generate code from array of inputs
    function generateCode(inputs) {
        if (!inputs || inputs.length === 0) return '';
        
        const cleanedParts = inputs
            .filter(input => input && input.toString().trim()) // Remove empty values
            .map(input => cleanInput(input.toString()))
            .filter(part => part.length > 0); // Remove empty parts after cleaning
        
        let code = cleanedParts.join(separator);
        
        // Limit to maxLength
        if (code.length > maxLength) {
            code = code.substring(0, maxLength);
            // Try to end at a separator to avoid cutting words
            const lastSeparatorIndex = code.lastIndexOf(separator);
            if (lastSeparatorIndex > 0 && lastSeparatorIndex > maxLength * 0.8) {
                code = code.substring(0, lastSeparatorIndex);
            }
        }
        
        return code;
    }
    
    // Clean individual input string
    function cleanInput(input) {
        return input
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '') // Remove special characters except spaces
            .replace(/\s+/g, separator) // Replace spaces with separator
            .replace(new RegExp(`\\${separator}+`, 'g'), separator) // Remove duplicate separators
            .replace(new RegExp(`^\\${separator}|\\${separator}$`, 'g'), ''); // Remove leading/trailing separators
    }
    
    // Handle manual input
    function handleInput(event) {
        const newValue = event.target.value;
        
        // If user manually changed the value (not from auto-generation)
        if (newValue !== suggestedCode) {
            manuallyEdited = true;
        }
        
        value = newValue;
        dispatch('input', { value });
    }
    
    // Reset to auto-generation
    function resetToAutoGenerate() {
        manuallyEdited = false;
        if (generateFrom.length > 0) {
            suggestedCode = generateCode(generateFrom);
            value = suggestedCode;
            dispatch('input', { value });
        }
    }
</script>

<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-mono" for="code-input">
        {label} {#if required}<span class="text-destructive">*</span>{/if}
    </label>
    
    <!-- Input and buttons in same row -->
    <div class="flex items-center gap-2">
        <input
            id="code-input"
            type="text"
            class="kt-input flex-1 {error ? 'kt-input-error' : ''}"
            {placeholder}
            {disabled}
            {value}
            maxlength={maxLength}
            on:input={handleInput}
        />
    </div>
    
    <!-- Error message -->
    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>