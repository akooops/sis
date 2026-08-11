<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let calendar;
    export let languages;
    export let translations;

    // Define breadcrumbs for this calendar
    const breadcrumbs = [
        {
            title: 'Calendars',
            url: route('admin.calendars.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.calendars.edit', { calendar: calendar?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Edit Calendar';

    // Form data for basic calendar info
    let form = {
        name: calendar?.name || '',
        file: null,
        starts_at: calendar?.starts_at ? new Date(calendar.starts_at).toISOString().slice(0, 10) : '',
        ends_at: calendar?.ends_at ? new Date(calendar.ends_at).toISOString().slice(0, 10) : '',
        is_active: calendar?.is_active || false
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Translation form data
    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    // Initialize translation forms immediately to prevent undefined errors
    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            // Get translation data - now always has values (either translation or fallback)
            const title = translations?.title?.[language.code] || '';
            
            translationForms[language.code] = {
                title: title
            };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

    // Handle file input change
    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            form.file = file;
        }
    }

    // Handle basic form submission
    function handleSubmit() {
        loading = true;
        
        const formData = new FormData();

        // Add method override for PATCH
        formData.append('_method', 'PATCH');
        formData.append('name', form.name);
        formData.append('starts_at', form.starts_at);
        formData.append('ends_at', form.ends_at);
        formData.append('is_active', form.is_active ? 1 : 0);
        
        // Add file if selected
        if (form.file) {
            formData.append('file', form.file);
        }

        router.post(route('admin.calendars.update', { calendar: calendar.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
        });
    }

    // Handle translation form submission
    function handleTranslationSubmit(languageCode, languageId) {
        translationLoading[languageCode] = true;
        translationErrors[languageCode] = {};

        // Get form data
        const formData = new FormData();

        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        formData.append('_method', 'PATCH');

        formData.append('language_id', languageId);
        formData.append('title', translationForms[languageCode].title);

        // Send AJAX request
        fetch(route('admin.calendars.update-translation', { calendar: calendar.id }), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw data;
                });
            }
            return response.json();
        })
        .then(data => {
            // Show success toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: `Translation for ${languageCode} updated successfully.`,
                variant: "success",
                position: "bottom-right",
            });
        })
        .catch(error => {
            // Handle validation errors
            if (error.errors) {
                translationErrors[languageCode] = error.errors;
            } else {
                // General error
                translationErrors[languageCode] = { general: ['An error occurred. Please try again.'] };
            }
        })
        .finally(() => {
            translationLoading[languageCode] = false;
        });
    }

    // Initialize components after mount
    onMount(async () => {
        await tick();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Calendar Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Calendar</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update calendar information and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.calendars.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Calendars
                    </a>
                </div>
            </div>

            <!-- Main Content with Tabs -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Language Tabs -->
                    <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                        <div class="flex items-center gap-5">
                            <button 
                                class="kt-tab-toggle py-3 active" 
                                data-kt-tab-toggle="#calendar_form_tab"
                            >
                                <i class="ki-filled ki-calendar text-base me-2"></i>
                                Edit calendar
                            </button>
                            <button 
                                class="kt-tab-toggle py-3" 
                                data-kt-tab-toggle="#translations_tab"
                            >
                                <i class="ki-filled ki-geolocation text-base me-2"></i>
                                Translations
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <!-- Calendar Form Tab -->
                    <div class="grow flex flex-col" id="calendar_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Basic Info Form -->
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Basic Information</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Calendar Name -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="name">
                                                Calendar Name <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="name"
                                                type="text"
                                                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                                placeholder="Enter calendar name"
                                                bind:value={form.name}
                                            />
                                            {#if errors.name}
                                                <p class="text-sm text-destructive">{errors.name}</p>
                                            {/if}
                                        </div>

                                        <!-- Calendar Start Date -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="starts_at">
                                                Start Date <span class="text-destructive">*</span>
                                            </label>
                                            <Flatpickr
                                                id="starts_at"
                                                bind:value={form.starts_at}
                                                placeholder="Select start date"
                                                config={{
                                                    dateFormat: 'Y-m-d',
                                                    minDate: 'today'
                                                }}
                                            />
                                            {#if errors.starts_at}
                                                <p class="text-sm text-destructive">{errors.starts_at}</p>
                                            {/if}
                                        </div>

                                        <!-- Calendar End Date -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="ends_at">
                                                End Date <span class="text-destructive">*</span>
                                            </label>
                                            <Flatpickr
                                                id="ends_at"
                                                bind:value={form.ends_at}
                                                placeholder="Select end date"
                                                config={{
                                                    dateFormat: 'Y-m-d',
                                                    minDate: form.starts_at || 'today'
                                                }}
                                            />
                                            {#if errors.ends_at}
                                                <p class="text-sm text-destructive">{errors.ends_at}</p>
                                            {/if}
                                        </div>

                                        <!-- Is Active Toggle -->
                                        <div class="flex items-center gap-2">
                                            <input 
                                                class="kt-switch" 
                                                type="checkbox" 
                                                id="is_active" 
                                                checked={form.is_active}
                                                on:change={(e) => {
                                                    form.is_active = e.target.checked;
                                                }}
                                            />
                                            <label class="kt-label" for="is_active">
                                                Is active
                                            </label>

                                            {#if errors.is_active}
                                                <p class="text-sm text-destructive">{errors.is_active}</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Calendar File Card -->
                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Calendar File</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <!-- Current File Display -->
                                        {#if calendar?.calendarUrl}
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium text-mono">Current File</label>
                                                <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-16 h-16 bg-primary/10 rounded-lg flex items-center justify-center">
                                                            <i class="ki-filled ki-file text-2xl text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-mono">{calendar.name}</span>
                                                        <span class="text-xs text-secondary-foreground">Calendar</span>
                                                        <a href={calendar.calendarUrl} target="_blank" class="text-xs text-primary hover:underline">
                                                            View File <i class="ki-filled ki-arrow-up-right"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        {/if}

                                        <!-- File Upload Section -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="file">
                                                Upload New File
                                            </label>
                                            <input
                                                id="file"
                                                type="file"
                                                class="kt-input"
                                                accept="*"
                                                on:change={handleFileChange}
                                            />
                                            <p class="text-xs text-secondary-foreground">
                                                Leave empty to keep the current file
                                            </p>
                                            {#if errors.file}
                                                <p class="text-sm text-destructive">{errors.file}</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{route('admin.calendars.index')}" class="kt-btn kt-btn-outline">
                                    Cancel
                                </a>
                                <button
                                    type="submit"
                                    class="kt-btn kt-btn-primary"
                                    disabled={loading}
                                    on:click|preventDefault={handleSubmit}
                                >
                                    {#if loading}
                                        <i class="ki-outline ki-loading text-base animate-spin"></i>
                                        Updating...
                                    {:else}
                                        <i class="ki-filled ki-check text-base"></i>
                                        Update Calendar
                                    {/if}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Translations Tab -->
                    <div class="grow flex flex-col hidden" id="translations_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <!-- Language Tabs -->
                            <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                                <div class="flex items-center gap-5">
                                    {#each languages as language, index}
                                        <button 
                                            class="kt-tab-toggle py-3 {index === 0 ? 'active' : ''}" 
                                            data-kt-tab-toggle="#translation_tab_{language.code}"
                                        >
                                            <i class="ki-filled ki-translate text-base me-2"></i>
                                            {language.name}
                                        </button>
                                    {/each}
                                </div>
                            </div>

                            <!-- Tab Content -->
                            {#each languages as language, index}
                                <div 
                                    class="grow flex flex-col {index === 0 ? '' : 'hidden'}" 
                                    id="translation_tab_{language.code}"
                                >
                                    <form 
                                        on:submit|preventDefault={() => handleTranslationSubmit(language.code, language.id)}
                                        class="grid gap-4"
                                    >
                                        <!-- Calendar Title -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter calendar title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <!-- Form Actions -->
                                        <div class="flex items-center justify-end gap-3 pt-4">
                                            <button
                                                type="submit"
                                                class="kt-btn kt-btn-success"
                                                disabled={translationLoading[language.code]}
                                            >
                                                {#if translationLoading[language.code]}
                                                    <i class="ki-outline ki-loading text-base animate-spin"></i>
                                                    Saving...
                                                {:else}
                                                    <i class="ki-filled ki-check text-base"></i>
                                                    Save Translation
                                                {/if}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 