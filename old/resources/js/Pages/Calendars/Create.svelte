<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this calendar
    const breadcrumbs = [
        {
            title: 'Calendars',
            url: route('admin.calendars.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.calendars.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Calendar';

    // Form data
    let form = {
        name: '',
        file: null,
        title: '',
        starts_at: '',
        ends_at: '',
        is_active: true
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Handle file input change
    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            form.file = file;
        }
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;
        
        const formData = new FormData();
        
        // Add form fields
        Object.keys(form).forEach(key => {
            if (form[key] !== null && form[key] !== '') {
                if (key === 'file' && form.file) {
                    formData.append(key, form.file);
                } else if(key === 'is_active') {
                    formData.append(key, form.is_active ? 1 : 0);
                } else {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.calendars.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
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
                    <h1 class="text-2xl font-bold text-mono">Create New Calendar</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new calendar to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.calendars.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Calendars
                    </a>
                </div>
            </div>

            <!-- Form -->
            <form on:submit|preventDefault={handleSubmit} class="grid gap-5 lg:gap-7.5">
                <!-- Basic Information Card -->
                <div class="kt-card">
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

                            <!-- Calendar Start Date/Time -->
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

                            <!-- Calendar End Date/Time -->
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
                                        time_24hr: false,
                                        minDate: form.starts_at || 'today'
                                    }}
                                />
                                {#if errors.ends_at}
                                    <p class="text-sm text-destructive">{errors.ends_at}</p>
                                {/if}
                            </div>

                            <!-- Media Option Selection -->
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
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Calendar File</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- File Upload Section -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="file">
                                    Upload File <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="file"
                                    type="file"
                                    class="kt-input"
                                    on:change={handleFileChange}
                                />
                                {#if errors.file}
                                    <p class="text-sm text-destructive">{errors.file}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Calendar Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Calendar Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Calendar Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter calendar title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
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
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin"></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-plus text-base"></i>
                            Create Calendar
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 