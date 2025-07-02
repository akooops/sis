<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import Summernote from '../Components/Forms/Summernote.svelte';
    import Flatpickr from '../Components/Forms/Flatpickr.svelte';

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this job posting
    const breadcrumbs = [
        {
            title: 'Job Postings',
            url: route('admin.job-postings.index'),
            active: false
        },
        {
            title: 'Create',
            url: route('admin.job-postings.create'),
            active: true
        }
    ];
    
    const pageTitle = 'Create Job Posting';

    // Form data
    let form = {
        name: '',
        slug: '',
        employment_type: 'full_time',
        is_remote: false,
        number_of_positions: 1,
        required_years_of_experience: '',
        application_deadline: '',
        status: 'draft',
        media_option: 'upload',
        file: null,
        media_id: '',
        title: '',
        description: '',
        content: '',
        required_skills: []
    };

    // Form errors
    let errors = {};

    // File preview
    let filePreview = null;

    // Loading state
    let loading = false;

    // Slug generation flag
    let slugManuallyEdited = false;

    // Dynamic data for selects
    let selectedMedia = null;

    // Select2 component references
    let mediaSelectComponent;
    let summernoteComponent;

    // Skills tags input
    let skillsInput = '';
    let skills = [];

    // Function to convert string to slug
    function stringToSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-')     // Replace spaces with hyphens
            .replace(/-+/g, '-')      // Replace multiple hyphens with single hyphen
            .trim();                  // Trim leading/trailing spaces
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
        // Update selected media for preview
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

    // Handle skills input
    function handleSkillsInput(event) {
        const value = event.target.value;
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addSkill(value);
        }
    }

    // Add skill
    function addSkill(skillText) {
        const trimmedSkill = skillText.trim();
        if (trimmedSkill && !skills.includes(trimmedSkill)) {
            skills = [...skills, trimmedSkill];
            form.required_skills = skills;
            skillsInput = '';
        }
    }

    // Remove skill
    function removeSkill(index) {
        skills = skills.filter((_, i) => i !== index);
        form.required_skills = skills;
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
                } else if (key === 'required_skills' && form.required_skills.length > 0) {
                    // Convert skills array to comma-separated string
                    formData.append(key, form.required_skills.join(','));
                } else if (key === 'is_remote') {
                    // Convert boolean to 1/0 for backend
                    formData.append(key, form[key] ? 1 : 0);
                } else if (key !== 'file' && key !== 'required_skills') {
                    formData.append(key, form[key]);
                }
            }
        });

        router.post(route('admin.job-postings.store'), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
                
                // Apply error styling to Select2 components
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
            <!-- Job Posting Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Create New Job Posting</h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new job posting to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.job-postings.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Job Postings
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
                            <!-- Job Name -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="name">
                                    Job Name <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                    placeholder="Enter job name"
                                    bind:value={form.name}
                                    on:input={handleNameChange}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">{errors.name}</p>
                                {/if}
                            </div>

                            <!-- Job Slug -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="slug">
                                    Job Slug <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="slug"
                                    type="text"
                                    class="kt-input {errors.slug ? 'kt-input-error' : ''}"
                                    placeholder="Enter job slug"
                                    bind:value={form.slug}
                                    on:input={handleSlugChange}
                                />
                                {#if errors.slug}
                                    <p class="text-sm text-destructive">{errors.slug}</p>
                                {/if}
                            </div>

                            <!-- Employment Type and Number of Positions -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="employment_type">
                                        Employment Type <span class="text-destructive">*</span>
                                    </label>
                                    <select
                                        id="employment_type"
                                        class="kt-select"
                                        bind:value={form.employment_type}
                                    >
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="internship">Internship</option>
                                    </select>
                                    {#if errors.employment_type}
                                        <p class="text-sm text-destructive">{errors.employment_type}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="number_of_positions">
                                        Number of Positions
                                    </label>
                                    <input
                                        id="number_of_positions"
                                        type="number"
                                        min="1"
                                        class="kt-input {errors.number_of_positions ? 'kt-input-error' : ''}"
                                        placeholder="Enter number of positions"
                                        bind:value={form.number_of_positions}
                                    />
                                    {#if errors.number_of_positions}
                                        <p class="text-sm text-destructive">{errors.number_of_positions}</p>
                                    {/if}
                                </div>
                            </div>

                            <!-- Remote Work Switch -->
                            <div class="flex items-center gap-2">
                                <input 
                                    class="kt-switch" 
                                    type="checkbox" 
                                    id="is_remote" 
                                    checked={form.is_remote}
                                    on:change={(e) => {
                                        form.is_remote = e.target.checked;
                                    }}
                                />
                                <label class="kt-label" for="is_remote">
                                    Remote Work Available
                                </label>
                                {#if errors.is_remote}
                                    <p class="text-sm text-destructive">{errors.is_remote}</p>
                                {/if}
                            </div>

                            <!-- Required Experience and Deadline -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="required_years_of_experience">
                                        Required Years of Experience
                                    </label>
                                    <input
                                        id="required_years_of_experience"
                                        type="number"
                                        min="0"
                                        max="50"
                                        class="kt-input {errors.required_years_of_experience ? 'kt-input-error' : ''}"
                                        placeholder="Enter years of experience"
                                        bind:value={form.required_years_of_experience}
                                    />
                                    {#if errors.required_years_of_experience}
                                        <p class="text-sm text-destructive">{errors.required_years_of_experience}</p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium text-mono" for="application_deadline">
                                        Application Deadline
                                    </label>
                                    <Flatpickr
                                        id="application_deadline"
                                        bind:value={form.application_deadline}
                                        placeholder="Select application deadline"
                                        config={{
                                            dateFormat: 'Y-m-d',
                                            minDate: 'today'
                                        }}
                                    />
                                    {#if errors.application_deadline}
                                        <p class="text-sm text-destructive">{errors.application_deadline}</p>
                                    {/if}
                                </div>
                            </div>

                            <!-- Job Status -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="status">
                                    Job Status <span class="text-destructive">*</span>
                                </label>
                                <select
                                    id="status"
                                    class="kt-select"
                                    bind:value={form.status}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="hidden">Hidden</option>
                                    <option value="published">Published</option>
                                </select>
                                {#if errors.status}
                                    <p class="text-sm text-destructive">{errors.status}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Selection Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Job Posting Thumbnail</h4>
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
                                        Upload Image <span class="text-destructive">*</span>
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
                                        Select Media <span class="text-destructive">*</span>
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
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Job Posting Content ({defaultLanguage.name})</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Job Title -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="title">
                                    Job Title <span class="text-destructive">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title ? 'kt-input-error' : ''}"
                                    placeholder="Enter job title"
                                    bind:value={form.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">{errors.title}</p>
                                {/if}
                            </div>

                            <!-- Job Description -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="description">
                                    Job Description <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    class="kt-textarea {errors.description ? 'kt-textarea-error' : ''}"
                                    placeholder="Enter job description"
                                    rows="3"
                                    bind:value={form.description}
                                ></textarea>
                                {#if errors.description}
                                    <p class="text-sm text-destructive">{errors.description}</p>
                                {/if}
                            </div>

                            <!-- Required Skills -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="skills-input">
                                    Required Skills
                                </label>
                                <div class="kt-input min-h-[38px] flex flex-wrap gap-2 items-center p-2">
                                    {#each skills as skill, index}
                                        <span class="kt-badge kt-badge-primary flex items-center gap-1">
                                            {skill}
                                            <button
                                                type="button"
                                                class="text-white hover:text-red-200 transition-colors"
                                                on:click={() => removeSkill(index)}
                                            >
                                                ×
                                            </button>
                                        </span>
                                    {/each}
                                    <input
                                        id="skills-input"
                                        type="text"
                                        class="flex-1 min-w-[100px] bg-transparent border-none outline-none"
                                        placeholder="Type skills and press Enter or comma to add them"
                                        bind:value={skillsInput}
                                        on:keydown={handleSkillsInput}
                                    />
                                </div>
                                <small class="text-muted">Type skills and press Enter or comma to add them</small>
                                {#if errors.required_skills}
                                    <p class="text-sm text-destructive">{errors.required_skills}</p>
                                {/if}
                            </div>

                            <!-- Job Content -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="summernote-editor">
                                    Job Content <span class="text-destructive">*</span>
                                </label>
                                <Summernote
                                    bind:this={summernoteComponent}
                                    id="summernote-editor"
                                    bind:value={form.content}
                                    placeholder="Enter job content"
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
                    <a href="{route('admin.job-postings.index')}" class="kt-btn kt-btn-outline">
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
                            Create Job Posting
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout> 