<script>
    import { onMount, onDestroy, createEventDispatcher } from 'svelte';

    // Props
    export let id = '';
    export let value = '';
    export let placeholder = '';
    export let disabled = false;
    export let readonly = false;
    export let required = false;
    export let className = '';
    export let config = {};

    // Event dispatcher
    const dispatch = createEventDispatcher();

    // Component state
    let inputElement;
    let flatpickrInstance;

    // Default configuration
    const defaultConfig = {
        enableTime: false,
        dateFormat: 'Y-m-d',
        allowInput: true,
        clickOpens: true,
        time_24hr: false,
        ...config
    };

    // Initialize Flatpickr
    function initializeFlatpickr() {
        if (typeof window !== 'undefined' && window.flatpickr && inputElement) {
            // Destroy existing instance if any
            if (flatpickrInstance) {
                flatpickrInstance.destroy();
            }

            // Create new instance
            flatpickrInstance = window.flatpickr(inputElement, {
                ...defaultConfig,
                onChange: function(selectedDates, dateStr, instance) {
                    value = dateStr;
                    dispatch('change', { value: dateStr, selectedDates, instance });
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    dispatch('open', { selectedDates, dateStr, instance });
                },
                onClose: function(selectedDates, dateStr, instance) {
                    dispatch('close', { selectedDates, dateStr, instance });
                },
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    dispatch('daycreate', { dayObj: dObj, dayStr: dStr, fp, dayElem });
                },
                onReady: function(selectedDates, dateStr, instance) {
                    dispatch('ready', { selectedDates, dateStr, instance });
                }
            });

            // Set initial value if provided
            if (value) {
                flatpickrInstance.setDate(value);
            }
        }
    }

    // Update value when prop changes
    $: if (flatpickrInstance && value !== flatpickrInstance.input.value) {
        flatpickrInstance.setDate(value);
    }

    // Update disabled state
    $: if (flatpickrInstance) {
        if (disabled) {
            flatpickrInstance.disable();
        } else {
            flatpickrInstance.enable();
        }
    }

    // Update readonly state
    $: if (flatpickrInstance) {
        flatpickrInstance.config.allowInput = !readonly;
    }

    // Methods exposed to parent
    export function getValue() {
        return value;
    }

    export function setValue(newValue) {
        if (flatpickrInstance) {
            flatpickrInstance.setDate(newValue);
        }
        value = newValue;
    }

    export function clear() {
        if (flatpickrInstance) {
            flatpickrInstance.clear();
        }
        value = '';
    }

    export function open() {
        if (flatpickrInstance) {
            flatpickrInstance.open();
        }
    }

    export function close() {
        if (flatpickrInstance) {
            flatpickrInstance.close();
        }
    }

    export function setError(hasError) {
        if (inputElement) {
            if (hasError) {
                inputElement.classList.add('kt-input-error');
            } else {
                inputElement.classList.remove('kt-input-error');
            }
        }
    }

    // Lifecycle
    onMount(() => {
        // Check if Flatpickr is loaded
        if (typeof window !== 'undefined' && !window.flatpickr) {
            console.warn('Flatpickr is not loaded. Please include the Flatpickr library.');
            return;
        }

        initializeFlatpickr();
    });

    onDestroy(() => {
        if (flatpickrInstance) {
            flatpickrInstance.destroy();
        }
    });
</script>

<input
    bind:this={inputElement}
    {id}
    type="text"
    {placeholder}
    {disabled}
    {readonly}
    {required}
    class="kt-input {className}"
    bind:value
    on:input={(e) => {
        value = e.target.value;
        dispatch('input', { value: e.target.value });
    }}
    on:blur={(e) => dispatch('blur', { value: e.target.value })}
    on:focus={(e) => dispatch('focus', { value: e.target.value })}
/>

<style>
    /* Custom styles for Flatpickr */
    :global(.flatpickr-calendar) {
        z-index: 9999;
    }
    
    :global(.flatpickr-day.selected) {
        background-color: var(--kt-primary);
        border-color: var(--kt-primary);
    }
    
    :global(.flatpickr-day.selected:hover) {
        background-color: var(--kt-primary-dark);
        border-color: var(--kt-primary-dark);
    }
</style> 