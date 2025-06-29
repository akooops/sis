<script>
    import { onMount, onDestroy } from 'svelte';

    export let id = '';
    export let value = '';
    export let placeholder = 'Enter content...';
    export let disabled = false;
    export let height = 400;
    export let minHeight = 300;
    export let maxHeight = 600;
    export let focus = false;
    export let codeviewFilter = false;
    export let codeviewIframeFilter = false;
    export let disableDragAndDrop = false;
    export let toolbar = [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'italic', 'clear']],
        ['fontname', ['fontname']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['height', ['height']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'video']],
        ['view', ['fullscreen', 'codeview', 'help']]
    ];
    export let popover = {
        image: [
            ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
            ['float', ['floatLeft', 'floatRight', 'floatNone']],
            ['remove', ['removeMedia']]
        ],
        link: [
            ['link', ['linkDialogShow', 'unlink']]
        ],
        table: [
            ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
            ['delete', ['deleteRow', 'deleteCol', 'deleteTable']],
        ],
        air: [
            ['color', ['color']],
            ['font', ['bold', 'underline', 'italic', 'clear']]
        ]
    };
    export let buttons = {};
    export let modules = {};
    export let callbacks = {};

    let textareaElement;
    let summernoteInstance;

    // Create event dispatcher
    function createEventDispatcher() {
        return {
            change: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:change', { detail: data }));
                }
            },
            focus: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:focus', { detail: data }));
                }
            },
            blur: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:blur', { detail: data }));
                }
            },
            keyup: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:keyup', { detail: data }));
                }
            },
            keydown: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:keydown', { detail: data }));
                }
            },
            paste: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:paste', { detail: data }));
                }
            },
            imageUpload: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:imageUpload', { detail: data }));
                }
            },
            mediaDelete: (data) => {
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('summernote:mediaDelete', { detail: data }));
                }
            }
        };
    }

    const dispatch = createEventDispatcher();

    onMount(() => {
        const initializeSummernote = () => {
            if (typeof globalThis.$ === 'undefined' || !globalThis.$.fn.summernote) {
                setTimeout(initializeSummernote, 100);
                return;
            }
            summernoteInstance = globalThis.$(textareaElement).summernote({
                height: 400,
                minHeight: 300,
                maxHeight: 600,
                focus: false,
                codeviewFilter: false,
                codeviewIframeFilter: false,
                disableDragAndDrop: false,
            });
            if (value) {
                globalThis.$(textareaElement).summernote('code', value);
            }
        };
        initializeSummernote();

        // Apply kt-textarea styling to Summernote
        globalThis.$(textareaElement).next('.note-editor').addClass('kt-summernote-editor');
        globalThis.$(textareaElement).next('.note-editor').find('.note-editing-area').addClass('kt-summernote-area');

        // Handle disabled state
        if (disabled) {
            globalThis.$(textareaElement).summernote('disable');
        }

        // Handle external value changes
        $: if (summernoteInstance && value !== undefined) {
            const currentValue = globalThis.$(textareaElement).summernote('code');
            if (currentValue !== value) {
                globalThis.$(textareaElement).summernote('code', value);
            }
        }

        // Handle disabled state changes
        $: if (summernoteInstance) {
            if (disabled) {
                globalThis.$(textareaElement).summernote('disable');
            } else {
                globalThis.$(textareaElement).summernote('enable');
            }
        }
    });

    onDestroy(() => {
        if (summernoteInstance) {
            globalThis.$(textareaElement).summernote('destroy');
        }
    });

    // Expose methods for parent components
    export function getValue() {
        return globalThis.$(textareaElement).summernote('code');
    }

    export function setValue(newValue) {
        globalThis.$(textareaElement).summernote('code', newValue);
    }

    export function getText() {
        return globalThis.$(textareaElement).summernote('text');
    }

    export function setText(newText) {
        globalThis.$(textareaElement).summernote('text', newText);
    }

    export function isEmpty() {
        return globalThis.$(textareaElement).summernote('isEmpty');
    }

    export function reset() {
        globalThis.$(textareaElement).summernote('reset');
    }

    export function undo() {
        globalThis.$(textareaElement).summernote('undo');
    }

    export function redo() {
        globalThis.$(textareaElement).summernote('redo');
    }

    export function focusEditor() {
        globalThis.$(textareaElement).summernote('focus');
    }

    export function blur() {
        globalThis.$(textareaElement).summernote('blur');
    }

    export function disable() {
        globalThis.$(textareaElement).summernote('disable');
    }

    export function enable() {
        globalThis.$(textareaElement).summernote('enable');
    }

    export function destroy() {
        if (summernoteInstance) {
            globalThis.$(textareaElement).summernote('destroy');
        }
    }

    // Method to apply error styling
    export function setError(hasError) {
        if (hasError) {
            globalThis.$(textareaElement).next('.note-editor').addClass('kt-summernote-error');
        } else {
            globalThis.$(textareaElement).next('.note-editor').removeClass('kt-summernote-error');
        }
    }
</script>

<textarea 
    bind:this={textareaElement}
    {id}
    {placeholder}
    {disabled}
    class="kt-textarea"
></textarea>

<style>

    /* Error state styling */
    .kt-summernote-editor.kt-summernote-error .note-editing-area {
        border-color: #ef4444 !important;
    }

    .kt-summernote-editor.kt-summernote-error .note-editing-area:focus-within {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    /* Focus state styling */
    .kt-summernote-editor .note-editing-area:focus-within {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }

    /* General styling */
    .kt-summernote-editor {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        background-color: #ffffff !important;
    }

    .kt-summernote-editor .note-toolbar {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem 0.5rem 0 0 !important;
        padding: 0.5rem !important;
    }

    .kt-summernote-editor .note-editing-area {
        border: none !important;
        border-radius: 0 0 0.5rem 0.5rem !important;
    }

    .kt-summernote-editor .note-editing-area .note-editable {
        padding: 1rem !important;
        min-height: 200px !important;
        color: #374151 !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
    }

    .kt-summernote-editor .note-editing-area .note-editable:focus {
        outline: none !important;
    }

    /* Button styling */
    .kt-summernote-editor .btn {
        border: 1px solid #d1d5db !important;
        background-color: #ffffff !important;
        color: #374151 !important;
        border-radius: 0.375rem !important;
        padding: 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
        margin: 0.125rem !important;
    }

    .kt-summernote-editor .btn:hover {
        background-color: #f3f4f6 !important;
        border-color: #9ca3af !important;
    }

    .kt-summernote-editor .btn.active {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
    }

    /* Dropdown styling */
    .kt-summernote-editor .dropdown-menu {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        background-color: #ffffff !important;
    }

    .kt-summernote-editor .dropdown-item {
        padding: 0.5rem 0.75rem !important;
        color: #374151 !important;
        font-size: 0.875rem !important;
    }

    .kt-summernote-editor .dropdown-item:hover {
        background-color: #f3f4f6 !important;
        color: #374151 !important;
    }

    /* Disabled state */
    .kt-summernote-editor.note-disabled {
        opacity: 0.6 !important;
        pointer-events: none !important;
    }

    .kt-summernote-editor.note-disabled .note-editing-area .note-editable {
        background-color: #f9fafb !important;
    }
</style> 