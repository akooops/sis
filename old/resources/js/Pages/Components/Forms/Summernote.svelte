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
    let isUploading = false;
    let uploadProgress = 0;
    let currentUploadFile = '';

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
                callbacks: {
                    onImageUpload: async function(files) {
                        isUploading = true;
                        uploadProgress = 0;
                        
                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            currentUploadFile = file.name;
                            try {
                                uploadProgress = 0;
                                const imageUrl = await uploadImage(file);
                                
                                globalThis.$(textareaElement).summernote('pasteHTML', `<img src="${imageUrl}" alt="${file.name}">`);
                                
                                // Small delay between uploads for better UX
                                if (i < files.length - 1) {
                                    await new Promise(resolve => setTimeout(resolve, 200));
                                }
                            } catch (error) {
                                console.error('Failed to upload image:', error);
                                // You could show a user-friendly error message here
                                alert(`Failed to upload ${file.name}. Please try again.`);
                            }
                        }
                        
                        currentUploadFile = '';
                        
                        isUploading = false;
                        uploadProgress = 0;
                    }
                }
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

    // Custom image upload function
    const uploadImage = async (file) => {
        const formData = new FormData();
        formData.append('file', file);
        
        // Simulate progress for better UX
        const progressInterval = setInterval(() => {
            if (uploadProgress < 90) {
                uploadProgress += Math.random() * 10;
            }
        }, 100);
        
        try {
            const response = await fetch(route('admin.files.upload'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            clearInterval(progressInterval);
            uploadProgress = 100;
            
            if (response.ok) {
                const result = await response.json();
                if (result.status === 'success' && result.data && result.data.file) {
                    return result.data.file.full_path;
                }
            }
            throw new Error('Upload failed');
        } catch (error) {
            clearInterval(progressInterval);
            console.error('Error uploading image:', error);
            throw error;
        }
    };

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

<div class="kt-summernote-wrapper">
    <!-- Loading Spinner -->
    {#if isUploading}
        <div class="kt-summernote-loading">
            <div class="kt-summernote-loading-content">
                <div class="kt-summernote-spinner">
                    <i class="ki-outline ki-loading text-xl animate-spin"></i>
                </div>
                <div class="kt-summernote-loading-text">
                    {#if currentUploadFile}
                        Uploading {currentUploadFile}...
                    {:else}
                        Uploading image...
                    {/if}
                </div>
                {#if uploadProgress > 0 && uploadProgress < 100}
                    <div class="kt-summernote-progress">
                        <div class="kt-summernote-progress-bar" style="width: {uploadProgress}%"></div>
                    </div>
                {/if}
            </div>
        </div>
    {/if}
    
    <textarea 
        bind:this={textareaElement}
        {id}
        {placeholder}
        {disabled}
        class="kt-textarea"
    ></textarea>
</div>

<style>
    /* Loading spinner styling */
    .kt-summernote-wrapper {
        position: relative;
    }

    .kt-summernote-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(2px);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
    }

    .kt-summernote-loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 2rem;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    .kt-summernote-spinner {
        color: #3b82f6;
    }

    .kt-summernote-loading-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        text-align: center;
    }

    .kt-summernote-progress {
        width: 200px;
        height: 4px;
        background-color: #e2e8f0;
        border-radius: 2px;
        overflow: hidden;
    }

    .kt-summernote-progress-bar {
        height: 100%;
        background-color: #3b82f6;
        border-radius: 2px;
        transition: width 0.3s ease;
    }

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