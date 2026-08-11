<script>
    import { onMount, onDestroy, createEventDispatcher } from 'svelte';

    export let id = '';
    export let placeholder = 'Select...';
    export let value = '';
    export let allowClear = true;
    export let disabled = false;
    export let ajax = null;
    export let templateResult = null;
    export let templateSelection = null;
    export let minimumInputLength = 0;
    export let delay = 300;
    export let cache = true;
    export let theme = 'bootstrap-5';
    export let dropdownCssClass = 'kt-select2-dropdown';
    export let containerCssClass = 'kt-select2-container';
    export let data = [];
    export let multiple = false;
    export let closeOnSelect = true;
    export let tags = false;
    export let tokenSeparators = [];
    export let maximumSelectionLength = null;
    export let minimumResultsForSearch = 0;
    export let maximumResultsForSearch = null;
    export let selectOnClose = false;
    export let width = '100%';

    let selectElement;
    let select2Instance;

    // Create Svelte event dispatcher
    const dispatch = createEventDispatcher();

    onMount(() => {
        if (typeof globalThis.$ === 'undefined' || !globalThis.$.fn.select2) {
            console.warn('Select2 or jQuery not loaded');
            return;
        }

        // Initialize Select2
        const options = {
            theme: theme,
            placeholder: placeholder,
            allowClear: allowClear,
            disabled: disabled,
            minimumInputLength: minimumInputLength,
            delay: delay,
            cache: cache,
            closeOnSelect: closeOnSelect,
            tags: tags,
            tokenSeparators: tokenSeparators,
            selectOnClose: selectOnClose,
            width: width,
            dropdownCssClass: dropdownCssClass,
            containerCssClass: containerCssClass
        };

        // Add optional properties if provided
        if (maximumSelectionLength) options.maximumSelectionLength = maximumSelectionLength;
        if (minimumResultsForSearch !== 0) options.minimumResultsForSearch = minimumResultsForSearch;
        if (maximumResultsForSearch) options.maximumResultsForSearch = maximumResultsForSearch;

        // Add AJAX configuration if provided
        if (ajax) {
            options.ajax = ajax;
        } else if (data && data.length > 0) {
            options.data = data;
        }

        // Add custom templates if provided
        if (templateResult) {
            options.templateResult = templateResult;
        }
        if (templateSelection) {
            options.templateSelection = templateSelection;
        }

        // Initialize Select2
        select2Instance = globalThis.$(selectElement).select2(options);

        // Set initial value
        if (value) {
            globalThis.$(selectElement).val(value).trigger('change');
        }

        // Apply kt-input styling to Select2
        globalThis.$(selectElement).next('.select2-container').find('.select2-selection').addClass('kt-input');
        globalThis.$(selectElement).next('.select2-container').find('.select2-selection--single').css({
            'height': 'auto',
            'min-height': '32px',
            'border': '1px solid #e2e8f0',
            'border-radius': '0.5rem',
            'background-color': '#ffffff',
            'padding': '0.5rem 0.75rem'
        });

        // Handle events
        globalThis.$(selectElement).on('select2:select', function(e) {
            const selectedData = e.params.data;
            value = multiple ? globalThis.$(selectElement).val() : selectedData.id;
            dispatch('select', { value: value, data: selectedData });
            dispatch('change', { value: value, data: selectedData });
            
            // Remove error styling when user selects something
            globalThis.$(selectElement).next('.select2-container').removeClass('kt-input-error');
        });

        globalThis.$(selectElement).on('select2:clear', function(e) {
            value = multiple ? [] : '';
            dispatch('clear');
            dispatch('change', { value: value, data: null });
        });

        globalThis.$(selectElement).on('select2:unselect', function(e) {
            if (multiple) {
                value = globalThis.$(selectElement).val();
                dispatch('change', { value: value, data: e.params.data });
            }
        });

        // Handle external value changes
        $: if (select2Instance && value !== undefined) {
            const currentValue = globalThis.$(selectElement).val();
            if (JSON.stringify(currentValue) !== JSON.stringify(value)) {
                globalThis.$(selectElement).val(value).trigger('change');
            }
        }

        // Handle disabled state changes
        $: if (select2Instance) {
            if (disabled) {
                globalThis.$(selectElement).prop('disabled', true).trigger('change');
            } else {
                globalThis.$(selectElement).prop('disabled', false).trigger('change');
            }
        }

        // Handle AJAX configuration changes
        $: if (select2Instance && ajax) {
            // Destroy and recreate Select2 when AJAX config changes
            globalThis.$(selectElement).select2('destroy');
            
            // Reinitialize with new AJAX config
            const options = {
                theme: theme,
                placeholder: placeholder,
                allowClear: allowClear,
                disabled: disabled,
                minimumInputLength: minimumInputLength,
                delay: delay,
                cache: cache,
                closeOnSelect: closeOnSelect,
                tags: tags,
                tokenSeparators: tokenSeparators,
                selectOnClose: selectOnClose,
                width: width,
                dropdownCssClass: dropdownCssClass,
                containerCssClass: containerCssClass,
                ajax: ajax
            };

            // Add optional properties if provided
            if (maximumSelectionLength) options.maximumSelectionLength = maximumSelectionLength;
            if (minimumResultsForSearch !== 0) options.minimumResultsForSearch = minimumResultsForSearch;
            if (maximumResultsForSearch) options.maximumResultsForSearch = maximumResultsForSearch;

            // Add custom templates if provided
            if (templateResult) {
                options.templateResult = templateResult;
            }
            if (templateSelection) {
                options.templateSelection = templateSelection;
            }

            select2Instance = globalThis.$(selectElement).select2(options);

            // Reapply styling
            globalThis.$(selectElement).next('.select2-container').find('.select2-selection').addClass('kt-input');
            globalThis.$(selectElement).next('.select2-container').find('.select2-selection--single').css({
                'height': 'auto',
                'min-height': '32px',
                'border': '1px solid #e2e8f0',
                'border-radius': '0.5rem',
                'background-color': '#ffffff',
                'padding': '0.5rem 0.75rem'
            });

            // Reattach event handlers
            globalThis.$(selectElement).off('select2:select select2:clear select2:unselect');
            
            globalThis.$(selectElement).on('select2:select', function(e) {
                const selectedData = e.params.data;
                value = multiple ? globalThis.$(selectElement).val() : selectedData.id;
                dispatch('select', { value: value, data: selectedData });
                dispatch('change', { value: value, data: selectedData });
                
                // Remove error styling when user selects something
                globalThis.$(selectElement).next('.select2-container').removeClass('kt-input-error');
            });

            globalThis.$(selectElement).on('select2:clear', function(e) {
                value = multiple ? [] : '';
                dispatch('clear');
                dispatch('change', { value: value, data: null });
            });

            globalThis.$(selectElement).on('select2:unselect', function(e) {
                if (multiple) {
                    value = globalThis.$(selectElement).val();
                    dispatch('change', { value: value, data: e.params.data });
                }
            });
        }
    });

    onDestroy(() => {
        if (select2Instance) {
            globalThis.$(selectElement).select2('destroy');
        }
    });

    // Expose methods for parent components
    export function getValue() {
        return globalThis.$(selectElement).val();
    }

    export function setValue(newValue) {
        globalThis.$(selectElement).val(newValue).trigger('change');
    }

    export function setData(data) {
        globalThis.$(selectElement).val(data).trigger('change');
    }

    export function open() {
        globalThis.$(selectElement).select2('open');
    }

    export function close() {
        globalThis.$(selectElement).select2('close');
    }

    export function destroy() {
        if (select2Instance) {
            globalThis.$(selectElement).select2('destroy');
        }
    }

    // Method to apply error styling
    export function setError(hasError) {
        if (hasError) {
            globalThis.$(selectElement).next('.select2-container').addClass('kt-input-error');
        } else {
            globalThis.$(selectElement).next('.select2-container').removeClass('kt-input-error');
        }
    }
</script>

<select 
    bind:this={selectElement}
    {id}
    {disabled}
    {multiple}
    class="form-select"
>
    {#if !ajax && !data}
        <option value="">{placeholder}</option>
    {/if}
</select>

<style>
    /* Error state styling */
    .kt-select2-container.kt-input-error .select2-selection--single {
        border-color: #ef4444 !important;
    }

    .kt-select2-container.kt-input-error .select2-selection--single:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    /* Focus state styling */
    .kt-select2-container .select2-selection--single:focus,
    .kt-select2-container.select2-container--focus .select2-selection--single {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }

    /* Dropdown styling */
    .kt-select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        background-color: #ffffff !important;
    }

    .kt-select2-dropdown .select2-results__option {
        padding: 0.5rem 0.75rem !important;
        color: #374151 !important;
    }

    .kt-select2-dropdown .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
    }

    .kt-select2-dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
    }

    .kt-select2-dropdown .select2-search__field:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }
</style> 