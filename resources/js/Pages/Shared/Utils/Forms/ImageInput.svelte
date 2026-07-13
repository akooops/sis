<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let value = null; // File object
    export let disabled = false;
    export let maxFileSize = maxUploadSize();  
    export let allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    export let defaultImage = '/assets/media/avatars/blank.png';
    export let size = 'w-24 h-24'; // Default size
    export let showFileInfo = true;

    // Local state
    let preview = null;
    let fileInput;
    let error = '';

    // Handle file selection
    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            // Set the file
            value = file;
            
            // Create preview
            const reader = new FileReader();
            reader.onload = (e) => {
                preview = e.target.result;
            };
            reader.readAsDataURL(file);

            // Dispatch change event
            dispatch('change', { file, preview });
        }
    }

    // Remove file
    function removeFile() {
        value = null;
        preview = null;
        error = '';
        if (fileInput) {
            fileInput.value = '';
        }
        dispatch('change', { file: null, preview: null });
    }

    // Trigger file input
    function triggerFileInput() {
        if (!disabled && fileInput) {
            fileInput.click();
        }
    }

    // Get current image source
    $: currentImage = preview || defaultImage;
</script>

<style>
    .input-overlay {
        display: none;
        opacity: 0;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .preview-image:hover .input-overlay {
        background-color: var(--mono);
        display: flex;
        opacity: 0.75;
    }
</style>

<div class="flex flex-col gap-2">
    <!-- Image Upload Area -->
    <div 
        class="relative inline-block cursor-pointer group {size}" 
        on:click={triggerFileInput}
        class:opacity-50={disabled}
        class:cursor-not-allowed={disabled}
    >
        <!-- Image Image -->
        <div class="relative rounded-lg overflow-hidden border-2 border-gray-200 group-hover:border-primary transition-colors preview-image {size}">
            <img 
                src={currentImage} 
                alt="Image" 
                class="{size} object-cover"
            />
            
            {#if !disabled}
                <!-- Overlay on hover -->
                <div class="absolute inset-0 bg-mono flex gap-4 items-center justify-center input-overlay">
                    <div class="text-center text-white">
                        <i class="fa-solid fa-camera text-xl"></i>
                    </div>

                    {#if value}
                        <div class="text-center text-white" on:click|stopPropagation={removeFile}>
                            <i class="fa-solid fa-times text-xl"></i>
                        </div>
                    {/if}
                </div>
            {/if}
        </div>
    </div>

    <!-- Hidden file input -->
    <input
        bind:this={fileInput}
        type="file"
        accept="{allowedTypes.join(',')}"
        class="hidden"
        on:change={handleFileSelect}
        {disabled}
    />

    <div class="text-xs text-muted-foreground">
        <i class="fa-solid fa-info-circle mr-1"></i>
        Maximum file size: {maxFileSize}.
    </div>
</div>