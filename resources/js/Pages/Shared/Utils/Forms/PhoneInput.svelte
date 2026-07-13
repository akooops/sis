<script>
    import { onMount, onDestroy } from 'svelte';

    export let value = '';
    export let placeholder = 'Enter phone number';
    export let disabled = false;
    export let initialCountry = 'sa';

    let phoneInput;
    let itiInstance;

    onMount(() => {
        if (typeof window.intlTelInput === 'undefined') {
            console.warn('intl-tel-input not loaded');
            return;
        }

        itiInstance = window.intlTelInput(phoneInput, {
            initialCountry: initialCountry,
            preferredCountries: ['sa', 'ae', 'kw', 'bh', 'om', 'qa'],
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
        });

        if (value) {
            itiInstance.setNumber(value);
        }

        // Update value when phone changes
        phoneInput.addEventListener('blur', updateValue);
        phoneInput.addEventListener('input', updateValue);
        phoneInput.addEventListener('countrychange', updateValue);
    });

    onDestroy(() => {
        if (itiInstance) {
            itiInstance.destroy();
        }
    });

    function updateValue() {
        if (itiInstance) {
            const fullNumber = itiInstance.getNumber();
            value = fullNumber || '';
        }
    }

    // Handle external value changes
    $: if (itiInstance && value) {
        itiInstance.setNumber(value);
    }
</script>

<input 
    bind:this={phoneInput}
    type="tel"
    {placeholder}
    {disabled}
    class="kt-input"
/>