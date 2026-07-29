<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this facility
    const breadcrumbs = [
        {
            title: 'Facilities',
            url: route('admin.facilities.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.facilities.create'),
            active: true
        }
    ];

    const pageTitle = 'Create Facility';

    // Form data
    let form = {
        name: '',
        slug: '',
        domain: '',
        status: 'draft',
        order: 0,
        email: '',
        phone: '',
        whatsapp: '',
        theme_primary_color: '#21262c',
        theme_secondary_color: '#21262c',
        media_option: 'upload',
        file: null,
        logo: null,
        media_id: '',
        title: '',
        tagline: '',
        description: '',
        content: '',
        address: ''
    };

    // Form errors
    let errors = {};

    // File previews
    let filePreview = null;
    let logoPreview = null;

    // Loading state
    let loading = false;

    // Slug generation flag
    let slugManuallyEdited = false;

    // Dynamic data for selects
    let selectedMedia = null;

    // Component references
    let mediaSelectComponent;
    let summernoteComponent;

    // Function to convert string to slug
    function stringToSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    // Handle name input change
    function handleNameChange() {
        if (!slugManuallyEdited) {
            form.slug = stringToSlug(form.name);
        }
    }

    // Handle slug input change
    function handleSlugChange() {
        slugManuallyEdited = true;
    }

    // Handle file input change
    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            form.file = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                filePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Handle logo input change
    function handleLogoChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            form.logo = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Handle media option change
    function handleMediaOptionChange() {
        if (form.media_option === 'upload') {
            form.media_id = '';
            selectedMedia = null;
        } else {
            form.file = null;
            filePreview = null;
        }
    }

    // Handle media selection
    function handleMediaSelect(event) {
        form.media_id = event.detail.value;
        if (event.detail.data) {
            selectedMedia = {
                id: event.detail.data.id,
                name: event.detail.data.text,
                file: { url: event.detail.data.mediaUrl }
            };
        }
    }

    // Handle media clear
    function handleMediaClear() {
        form.media_id = '';
        selectedMedia = null;
    }

    // Handle form submission
    function handleSubmit() {
        loading = true;

        // Ensure form.content is up-to-date from Summernote
        if (summernoteComponent && summernoteComponent.getValue) {
            form.content = summernoteComponent.getValue();
        }

        const formData = new FormData();

        // Add form fields
        Object.keys(form).forEach(key => {
            if (form[key] !== null && form[key] !== '') {
                if (key === 'file' && form.file) {
                    formData.append(key, form.file);
                } else if (key === 'logo' && form.logo) {
                    formData.append(key, form.logo);
                } else if (!['file', 'logo', 'theme_primary_color', 'theme_secondary_color'].includes(key)) {
                    formData.append(key, form[key]);
                }
            }
        });

        // Theme settings
        formData.append('theme[primary_color]', form.theme_primary_color);
        formData.append('theme[secondary_color]', form.theme_secondary_color);

        router.post(route('admin.facilities.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;

                // Apply error styling to components
                if (errors.media_id && mediaSelectComponent) {
                    mediaSelectComponent.setError(true);
                }
                if (errors.content && summernoteComponent) {
                    summernoteComponent.setError(true);
                }
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
            <!-- Facility Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Facility</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new facility with its own mini-website, contact and reservation system
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.facilities.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Facilities
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
                            <!-- Facility Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Facility Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter facility name"
                                    bind:value={form.name}
                                    on:input={handleNameChange}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Slug & Subdomain -->
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="slug">
                                        Slug <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        id="slug"
                                        type="text"
                                        class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                        placeholder="e.g. the-hive"
                                        bind:value={form.slug}
                                        on:input={handleSlugChange}
                                    />
                                    <p class="text-xs text-secondary-foreground">
                                        Path access: /facilities/&lt;slug&gt;
                                    </p>
                                    {#if errors.slug}
                                        <p class="text-sm text-destructive">{errors.slug}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="domain">
                                        Subdomain
                                    </label>
                                    <input
                                        id="domain"
                                        type="text"
                                        class="kt-input {errors.domain ? 'kt-input-error' : ''}"
                                        placeholder="e.g. hive"
                                        bind:value={form.domain}
                                    />
                                    <p class="text-xs text-secondary-foreground">
                                        Subdomain access: &lt;subdomain&gt;.your-domain (leave empty to disable)
                                    </p>
                                    {#if errors.domain}
                                        <p class="text-sm text-destructive">{errors.domain}</p>
                                    {/if}
                                </div>
                            </div>

                            <!-- Status & Order -->
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="status">
                                        Status <span class="text-destructive">*</span>
                                    </label>
                                    <select id="status" class="kt-select {errors.status ? 'kt-input-error' : ''}" bind:value={form.status}>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                    {#if errors.status}
                                        <p class="text-sm text-destructive">{errors.status}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="order">
                                        Order
                                    </label>
                                    <input
                                        id="order"
                                        type="number"
                                        min="0"
                                        class="kt-input {errors.order ? 'kt-input-error' : ''}"
                                        bind:value={form.order}
                                    />
                                    {#if errors.order}
                                        <p class="text-sm text-destructive">{errors.order}</p>
                                    {/if}
                                </div>
                            </div>

                            <!-- Theme Colors -->
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="theme-primary">
                                        Theme Primary Color
                                    </label>
                                    <input
                                        id="theme-primary"
                                        type="color"
                                        class="kt-input h-10 w-24 p-1"
                                        bind:value={form.theme_primary_color}
                                    />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="theme-secondary">
                                        Theme Secondary Color
                                    </label>
                                    <input
                                        id="theme-secondary"
                                        type="color"
                                        class="kt-input h-10 w-24 p-1"
                                        bind:value={form.theme_secondary_color}
                                    />
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="grid lg:grid-cols-3 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="email">
                                        Contact Email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        class="kt-input {errors.email ? 'kt-input-error' : ''}"
                                        placeholder="facility@example.com"
                                        bind:value={form.email}
                                    />
                                    {#if errors.email}
                                        <p class="text-sm text-destructive">{errors.email}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="phone">
                                        Phone
                                    </label>
                                    <input
                                        id="phone"
                                        type="text"
                                        class="kt-input {errors.phone ? 'kt-input-error' : ''}"
                                        placeholder="+9665xxxxxxxx"
                                        bind:value={form.phone}
                                    />
                                    {#if errors.phone}
                                        <p class="text-sm text-destructive">{errors.phone}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="whatsapp">
                                        WhatsApp Number
                                    </label>
                                    <input
                                        id="whatsapp"
                                        type="text"
                                        class="kt-input {errors.whatsapp ? 'kt-input-error' : ''}"
                                        placeholder="+9665xxxxxxxx"
                                        bind:value={form.whatsapp}
                                    />
                                    {#if errors.whatsapp}
                                        <p class="text-sm text-destructive">{errors.whatsapp}</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Hero Image & Logo</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Media Option Selection -->
                            <div class="flex items-center gap-2">
                                <input
                                    class="kt-switch"
                                    type="checkbox"
                                    id="media-switch"
                                    checked={form.media_option === 'select'}
                                    on:change={(e) => {
                                        form.media_option = e.target.checked ? 'select' : 'upload';
                                        handleMediaOptionChange();
                                    }}
                                />
                                <label class="kt-label" for="media-switch">
                                    Select from Media Library
                                </label>
                            </div>

                            <!-- File Upload Section -->
                            {#if form.media_option === 'upload'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="file">
                                        Upload Hero Image <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        id="file"
                                        type="file"
                                        class="kt-input"
                                        accept="image/*"
                                        on:change={handleFileChange}
                                    />
                                    {#if filePreview}
                                        <div class="mt-2">
                                            <img src={filePreview} alt="Preview" class="w-32 h-32 object-cover rounded-lg border" />
                                        </div>
                                    {/if}
                                    {#if errors.file}
                                        <p class="text-sm text-destructive">{errors.file}</p>
                                    {/if}
                                </div>
                            {/if}

                            <!-- Media Select Section -->
                            {#if form.media_option === 'select'}
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="media-select">
                                        Select Hero Image <span class="text-destructive">*</span>
                                    </label>
                                    <Select2
                                        bind:this={mediaSelectComponent}
                                        id="media-select"
                                        placeholder="Select media..."
                                        bind:value={form.media_id}
                                        on:select={handleMediaSelect}
                                        on:clear={handleMediaClear}
                                        ajax={{
                                            url: route('admin.media.index'),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term,
                                                    type: 'image',
                                                    perPage: 10
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.medias.map(media => ({
                                                        id: media.id,
                                                        text: media.name,
                                                        mediaUrl: media.file?.url || ''
                                                    }))
                                                };
                                            },
                                            cache: true
                                        }}
                                        templateResult={function(data) {
                                            if (data.loading) return data.text;
                                            if (!data.id) return data.text;

                                            return globalThis.$('<div class="d-flex align-items-center">' +
                                                '<img src="' + data.mediaUrl + '" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">' +
                                                '<span>' + data.text + '</span>' +
                                                '</div>');
                                        }}
                                        templateSelection={function(data) {
                                            if (!data.id) return data.text;

                                            return globalThis.$('<div class="d-flex flex-column align-items-center">' +
                                                '<img src="' + data.mediaUrl + '" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 3px;">' +
                                                '<span>' + data.text + '</span>' +
                                                '</div>');
                                        }}
                                    />

                                    <!-- Media Preview -->
                                    {#if form.media_id && selectedMedia}
                                        <div class="mt-2">
                                            <img
                                                src={selectedMedia.file?.url}
                                                alt={selectedMedia.name}
                                                class="w-32 h-32 object-cover rounded-lg border"
                                            />
                                        </div>
                                    {/if}

                                    {#if errors.media_id}
                                        <p class="text-sm text-destructive">{errors.media_id}</p>
                                    {/if}
                                </div>
                            {/if}

                            <!-- Logo Upload -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="logo">
                                    Logo
                                </label>
                                <input
                                    id="logo"
                                    type="file"
                                    class="kt-input"
                                    accept="image/*"
                                    on:change={handleLogoChange}
                                />
                                {#if logoPreview}
                                    <div class="mt-2">
                                        <img src={logoPreview} alt="Logo preview" class="w-20 h-20 object-contain rounded border" />
                                    </div>
                                {/if}
                                {#if errors.logo}
                                    <p class="text-sm text-destructive">{errors.logo}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Facility Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Facility Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Facility Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter facility title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Facility Tagline -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="tagline">
                                    Tagline
                                </label>
                                <input
                                    id="tagline"
                                    type="text"
                                    class="kt-input {errors.tagline ? 'kt-input-error' : ''}"
                                    placeholder="Short catchphrase shown under the title"
                                    bind:value={form.tagline}
                                />
                                {#if errors.tagline}
                                    <p class="text-sm text-destructive">{errors.tagline}</p>
                                {/if}
                            </div>

                            <!-- Facility Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Facility Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter facility description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Facility Address -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="address">
                                    Address
                                </label>
                                <input
                                    id="address"
                                    type="text"
                                    class="kt-input {errors.address ? 'kt-input-error' : ''}"
                                    placeholder="Facility address shown in the footer and contact page"
                                    bind:value={form.address}
                                />
                                {#if errors.address}
                                    <p class="text-sm text-destructive">{errors.address}</p>
                                {/if}
                            </div>

                            <!-- Facility Content -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="summernote-editor">
                                    Facility Content <span class="text-destructive">*</span>
                                </label>
                                <Summernote
                                    bind:this={summernoteComponent}
                                    id="summernote-editor"
                                    bind:value={form.content}
                                    placeholder="Enter facility content"
                                    height={400}
                                    minHeight={300}
                                    maxHeight={600}
                                    on:change={(event) => {
                                        form.content = event.detail.contents;
                                    }}
                                />
                                {#if errors.content}
                                    <p class="text-sm text-destructive">{errors.content}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{route('admin.facilities.index')}" class="kt-btn kt-btn-outline">
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
                            Create Facility
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
