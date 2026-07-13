<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let files = [];
    export let existingDocuments = [];
    export let disabled = false;
    export let allowDelete = false;
    export let error = null;
    export let label = 'Documents';
    export let helpText = 'PDF and CSV files only. You can add multiple files.';

    let fileInput;
    let displayedExistingDocuments = [];
    let deletingDocumentId = null;

    $: displayedExistingDocuments = [...(existingDocuments || [])];

    function handleFilesChange(event) {
        const selected = Array.from(event.target.files || []);

        if (selected.length === 0) {
            return;
        }

        files = [...files, ...selected];
        dispatch('change', { files });

        if (fileInput) {
            fileInput.value = '';
        }
    }

    function removeFile(index) {
        files = files.filter((_, fileIndex) => fileIndex !== index);
        dispatch('change', { files });
    }

    async function deleteExistingDocument(fileDocument) {
        const confirmed = confirm(`Are you sure you want to delete "${fileDocument.original_name}"?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        deletingDocumentId = fileDocument.id;

        try {
            const response = await fetch(route('api.v1.admin.files.destroy', { file: fileDocument.id }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (response.ok) {
                displayedExistingDocuments = displayedExistingDocuments.filter((item) => item.id !== fileDocument.id);
                dispatch('deleted', { document: fileDocument });
                toast('Document deleted successfully', 'success');
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot delete document:\n';
                Object.entries(data.errors).forEach(([, message]) => {
                    errorMessage += `• ${message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while deleting the document.', 'error');
            }
        } catch (err) {
            console.error('Network error:', err);
            toast('Network error occurred. Please try again.', 'error');
        } finally {
            deletingDocumentId = null;
        }
    }

    function triggerFileInput() {
        if (!disabled && fileInput) {
            fileInput.click();
        }
    }
</script>

<div class="flex flex-col gap-2">
    {#if label}
        <label class="text-sm font-medium text-mono">{label}</label>
    {/if}

    {#if displayedExistingDocuments.length}
        <div class="space-y-2">
            {#each displayedExistingDocuments as document (document.id)}
                <div class="flex items-center justify-between gap-2 rounded-lg border border-border px-3 py-2">
                    <a
                        href={document.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2 min-w-0 flex-1 hover:text-primary"
                    >
                        <i class="fa-solid fa-file text-muted-foreground shrink-0"></i>
                        <span class="text-sm truncate">{document.original_name}</span>
                    </a>
                    {#if allowDelete && !disabled && hasPermission('files.destroy')}
                        <button
                            type="button"
                            class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost text-destructive shrink-0"
                            title="Delete document"
                            disabled={deletingDocumentId === document.id}
                            on:click={() => deleteExistingDocument(document)}
                        >
                            {#if deletingDocumentId === document.id}
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            {:else}
                                <i class="fa-solid fa-xmark"></i>
                            {/if}
                        </button>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}

    {#if files.length}
        <div class="space-y-2">
            {#each files as file, index}
                <div class="flex items-center justify-between gap-2 rounded-lg border border-border px-3 py-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fa-solid fa-file text-muted-foreground"></i>
                        <span class="text-sm truncate">{file.name}</span>
                    </div>
                    {#if !disabled}
                        <button
                            type="button"
                            class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost text-destructive shrink-0"
                            title="Remove file"
                            on:click={() => removeFile(index)}
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}

    <input
        bind:this={fileInput}
        type="file"
        class="hidden"
        accept=".pdf,.csv,application/pdf,text/csv"
        multiple
        disabled={disabled}
        on:change={handleFilesChange}
    />

    <button
        type="button"
        class="kt-btn kt-btn-sm kt-btn-outline w-fit"
        disabled={disabled}
        on:click={triggerFileInput}
    >
        <i class="fa-solid fa-plus mr-1"></i>
        Add documents
    </button>

    {#if helpText}
        <p class="text-xs text-muted-foreground">{helpText}</p>
    {/if}

    {#if error}
        <p class="text-sm text-destructive">{error}</p>
    {/if}
</div>
