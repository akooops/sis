<script>
    import { createEventDispatcher } from 'svelte';
    import CodeInput from '../../Shared/Utils/Forms/CodeInput.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    const emptyForm = () => ({
        name: '',
        code: '',
        description: '',
        production_site_id: '',
        production_process_id: '',
    });

    let form = emptyForm();
    let errors = {};
    let loading = false;
    let siteSelectComponent;
    let processSelectComponent;

    $: processSelectKey = form.production_site_id || 'empty';

    $: processAjax = form.production_site_id ? {
        url: route('api.v1.admin.production-processes.index', { productionSite: form.production_site_id }),
        dataType: 'json',
        delay: 300,
        data: function(params) {
            return {
                search: params.term,
                per_page: 10,
            };
        },
        processResults: function(data) {
            return {
                results: data.production_processes.map(process => ({
                    id: process.id,
                    text: process.name,
                }))
            };
        },
        cache: false
    } : null;

    function handleSiteChange(event) {
        const newSiteId = event.detail.value || '';

        if (form.production_site_id !== newSiteId) {
            form.production_process_id = '';
        }

        form.production_site_id = newSiteId;
    }

    function handleProcessChange(event) {
        form.production_process_id = event.detail.value || '';
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            let formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.production-lines.store'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                errors = {};

                if (siteSelectComponent) {
                    siteSelectComponent.setValue('');
                }

                toast('Production line created successfully', 'success');
                dispatch('created');
            } else {
                if (data.errors) {
                    errors = data.errors;
                    if (errors.production_site_id && siteSelectComponent) {
                        siteSelectComponent.setError(true);
                    }
                    if (errors.production_process_id && processSelectComponent) {
                        processSelectComponent.setError(true);
                    }
                } else {
                    console.error('Error creating production line:', data.message || 'Unknown error');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = emptyForm();
        errors = {};

        if (siteSelectComponent) {
            siteSelectComponent.setValue('');
        }

        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Production Line</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="production-site-select">
                        Production Site <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={siteSelectComponent}
                        id="production-site-select"
                        placeholder="Search and select production site..."
                        value={form.production_site_id}
                        on:change={handleSiteChange}
                        disabled={loading}
                        ajax={{
                            url: route('api.v1.admin.production-sites.index'),
                            dataType: 'json',
                            delay: 300,
                            data: function(params) {
                                return {
                                    search: params.term,
                                    per_page: 10,
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.production_sites.map(site => ({
                                        id: site.id,
                                        text: site.name,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.production_site_id}
                        <p class="text-sm text-destructive">{errors.production_site_id}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="production-process-select">
                        Production Process <span class="text-destructive">*</span>
                    </label>
                    {#key processSelectKey}
                        <Select2
                            bind:this={processSelectComponent}
                            id="production-process-select"
                            placeholder={form.production_site_id ? 'Search and select production process...' : 'Select a production site first...'}
                            value={form.production_process_id}
                            on:change={handleProcessChange}
                            disabled={loading || !form.production_site_id}
                            ajax={processAjax}
                        />
                    {/key}
                    {#if errors.production_process_id}
                        <p class="text-sm text-destructive">{errors.production_process_id}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="name">
                        Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        class="kt-input {errors.name ? 'kt-input-error' : ''}"
                        placeholder="Enter production line name"
                        bind:value={form.name}
                        disabled={loading}
                    />
                    {#if errors.name}
                        <p class="text-sm text-destructive">{errors.name}</p>
                    {/if}
                </div>

                <CodeInput
                    bind:value={form.code}
                    label="Code"
                    placeholder="Enter code or leave blank to auto-generate from name"
                    generateFrom={[form.name]}
                    disabled={loading}
                    error={errors.code}
                />
                <p class="text-xs text-muted-foreground -mt-3">
                    Used for external systems and integrations (e.g. SAP, Power BI).
                </p>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="description">Description</label>
                    <textarea
                        id="description"
                        class="kt-textarea {errors.description ? 'kt-input-error' : ''}"
                        placeholder="Enter description"
                        bind:value={form.description}
                        rows="3"
                        disabled={loading}
                    ></textarea>
                    {#if errors.description}
                        <p class="text-sm text-destructive">{errors.description}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button
                type="button"
                class="kt-btn kt-btn-secondary"
                on:click={handleCancel}
                disabled={loading}
            >
                Cancel
            </button>
            <button
                type="submit"
                class="kt-btn kt-btn-primary"
                disabled={loading}
            >
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Creating...
                {:else}
                    <i class="fa-solid fa-conveyor-belt-boxes mr-2"></i>
                    Create Production Line
                {/if}
            </button>
        </div>
    </form>
</div>
