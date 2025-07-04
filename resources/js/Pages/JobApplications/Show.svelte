<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let jobApplication;
    export let languages;
    export let translations;

    console.log(jobApplication);
    
    // Define breadcrumbs for this job application
    const breadcrumbs = [
        {
            title: 'Job Applications',
            url: route('admin.job-applications.index', { jobPosting: jobApplication?.job_posting?.id }),
            active: false
        },
        {
            title: jobApplication?.job_posting?.getLocalTranslation?.('title') || jobApplication?.job_posting?.name || 'Job',
            url: route('admin.job-applications.index', { jobPosting: jobApplication?.job_posting?.id }),
            active: false
        },
        {
            title: `Application #${jobApplication?.id}` || 'Application Details',
            url: route('admin.job-applications.show', { jobApplication: jobApplication?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Job Application Details';

    // Get AI score badge class
    function getAiScoreBadgeClass(score) {
        if (!score) return 'kt-badge-secondary';
        if (score >= 8) return 'kt-badge-success';
        if (score >= 6) return 'kt-badge-warning';
        return 'kt-badge-danger';
    }

    // Get AI score text
    function getAiScoreText(score) {
        if (!score) return 'Pending score';
        return `${score}/10`;
    }

    // Get language proficiency badge class
    function getLanguageProficiencyBadgeClass(proficiency) {
        switch (proficiency) {
            case 'basic':
                return 'kt-badge-secondary';
            case 'intermediate':
                return 'kt-badge-warning';
            case 'advanced':
                return 'kt-badge-primary';
            case 'native':
                return 'kt-badge-success';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get language proficiency text
    function getLanguageProficiencyText(proficiency) {
        switch (proficiency) {
            case 'basic':
                return 'Basic';
            case 'intermediate':
                return 'Intermediate';
            case 'advanced':
                return 'Advanced';
            case 'native':
                return 'Native';
            default:
                return proficiency;
        }
    }

    // Get skills array
    function getSkillsArray(skills) {
        if (!skills) return [];
        return skills.split(',').map(skill => skill.trim()).filter(skill => skill);
    }

    // Calculate years of experience
    function calculateYears(startYear, endYear, isCurrent) {
        const end = isCurrent ? new Date().getFullYear() : endYear;
        const years = end - startYear;
        return years === 1 ? '1 year' : `${years} years`;
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <!-- Job Application Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold text-mono">Job Application Information</h1>
                <p class="text-sm text-secondary-foreground">
                    View job application details
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{route('admin.job-applications.index', { jobPosting: jobApplication?.job_posting?.id })}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left text-base"></i>
                    Back to applications
                </a>
            </div>
        </div>

        <div class="mt-5">
            <!-- begin: grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5">
                <!-- Left Sidebar -->
                <div class="col-span-1">
                    <div class="grid gap-5 lg:gap-7.5">
                        <!-- Personal Information Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">About</h3>
                            </div>
                            <div class="kt-card-content pt-4 pb-3">
                                <table class="kt-table-auto">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Email:</td>
                                            <td class="text-sm text-mono pb-3.5">
                                                <a href="mailto:{jobApplication?.email}" class="text-primary hover:text-primary-dark">
                                                    {jobApplication?.email || 'N/A'}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Phone:</td>
                                            <td class="text-sm text-mono pb-3.5">
                                                <a href="tel:{jobApplication?.phone}" class="text-primary hover:text-primary-dark">
                                                    {jobApplication?.phone || 'N/A'}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Nationality:</td>
                                            <td class="text-sm text-mono pb-3.5">{jobApplication?.nationality || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Address:</td>
                                            <td class="text-sm text-mono pb-3.5">{jobApplication?.address || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-secondary-foreground pb-3.5 pe-3">Applied:</td>
                                            <td class="text-sm text-mono pb-3.5">
                                                {jobApplication?.created_at ? new Date(jobApplication.created_at).toLocaleDateString('en-US', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit'
                                                }) : 'N/A'}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Work Experience Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Work Experience</h3>
                            </div>
                            <div class="kt-card-content">
                                {#if jobApplication?.experiences && jobApplication.experiences.length > 0}
                                    <div class="grid gap-y-5">
                                        {#each jobApplication.experiences as experience, index}
                                            <div class="flex align-start gap-3.5">
                                                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
                                                    <i class="ki-filled ki-briefcase text-lg text-primary"></i>
                                                </div>
                                                <div class="flex flex-col gap-1">
                                                    <a class="text-sm font-medium text-primary leading-none hover:text-primary" href="#">
                                                        {experience.company_name}
                                                    </a>
                                                    <span class="text-sm font-medium text-mono">
                                                        {experience.job_title}
                                                    </span>
                                                    <span class="text-xs text-secondary-foreground leading-none">
                                                        {experience.start_year} - {experience.is_current ? 'Present' : experience.end_year}
                                                        ({calculateYears(experience.start_year, experience.end_year, experience.is_current)})
                                                    </span>
                                                </div>
                                            </div>
                                            {#if index < jobApplication.experiences.length - 1}
                                                <div class="text-secondary-foreground font-semibold text-sm leading-none">
                                                    Previous Jobs
                                                </div>
                                            {/if}
                                        {/each}
                                    </div>
                                {:else}
                                    <div class="text-center py-4">
                                        <p class="text-sm text-secondary-foreground">No work experience provided</p>
                                    </div>
                                {/if}
                            </div>
                        </div>

                        <!-- Skills Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Skills</h3>
                            </div>
                            <div class="kt-card-content">
                                {#if getSkillsArray(jobApplication?.skills).length > 0}
                                    <div class="flex flex-wrap gap-2.5 mb-2">
                                        {#each getSkillsArray(jobApplication?.skills) as skill}
                                            <span class="kt-badge kt-badge-outline">{skill}</span>
                                        {/each}
                                    </div>
                                {:else}
                                    <p class="text-sm text-secondary-foreground">No skills specified</p>
                                {/if}
                            </div>
                        </div>

                        <!-- Documents Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Documents</h3>
                            </div>
                            <div class="kt-card-content">
                                {#if jobApplication?.cv}
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center grow gap-2.5">
                                            <i class="ki-filled ki-files text-lg text-primary"></i>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-mono cursor-pointer hover:text-primary mb-px">
                                                    {jobApplication.cv?.original_name}
                                                </span>

                                                <span class="text-xs text-secondary-foreground">
                                                    Uploaded: {jobApplication?.created_at ? new Date(jobApplication.created_at).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'short',
                                                        day: 'numeric'
                                                    }) : 'N/A'}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="kt-menu" data-kt-menu="true">
                                            <div class="kt-menu-item kt-menu-item-dropdown" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href={jobApplication.cv?.url} target="_blank">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-document"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Download</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {:else}
                                    <p class="text-sm text-secondary-foreground">No documents uploaded</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="col-span-2">
                    <div class="flex flex-col gap-5 lg:gap-7.5">
                        <!-- Header Card -->
                        <div class="kt-card">
                            <div class="kt-card-content px-10 py-7.5 lg:pe-12.5">
                                <div class="flex flex-wrap md:flex-nowrap items-center gap-6 md:gap-10">
                                    <div class="flex flex-col gap-3">
                                        <h2 class="text-xl font-semibold text-mono">
                                            {jobApplication?.first_name} {jobApplication?.last_name}
                                            <br>
                                            <span class="text-lg text-secondary-foreground">
                                                {jobApplication?.job_posting?.getLocalTranslation?.('title') || jobApplication?.job_posting?.name}
                                            </span>
                                        </h2>
                                        {#if jobApplication?.ai_score}
                                            <div class="flex items-center gap-2">
                                                <span class="kt-badge {getAiScoreBadgeClass(jobApplication.ai_score)}">
                                                    AI Score: {getAiScoreText(jobApplication.ai_score)}
                                                </span>
                                            </div>
                                        {/if}
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="w-32 h-32 rounded-full bg-primary/10 flex items-center justify-center">
                                            <i class="ki-filled ki-profile-user text-4xl text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Education Background Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Education Background</h3>
                            </div>
                            <div class="kt-card-content">
                                {#if jobApplication?.education && jobApplication.education.length > 0}
                                    <div class="kt-scrollable-x-auto">
                                        <table class="kt-table kt-table-border">
                                            <thead>
                                                <tr>
                                                    <th>Institution</th>
                                                    <th>Degree</th>
                                                    <th>Field of Study</th>
                                                    <th>Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {#each jobApplication.education as education}
                                                    <tr>
                                                        <td>
                                                            <h6 class="text-sm font-medium text-mono mb-1">{education.institution}</h6>
                                                        </td>
                                                        <td>
                                                            <span class="text-sm text-secondary-foreground">{education.degree}</span>
                                                        </td>
                                                        <td>
                                                            <span class="text-sm text-secondary-foreground">{education.field_of_study}</span>
                                                        </td>
                                                        <td>
                                                            <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                                {education.start_year} - {education.end_year}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    {#if education.description}
                                                        <tr>
                                                            <td colspan="4">
                                                                <small class="text-sm text-secondary-foreground">{education.description}</small>
                                                            </td>
                                                        </tr>
                                                    {/if}
                                                {/each}
                                            </tbody>
                                        </table>
                                    </div>
                                {:else}
                                    <div class="text-center py-8">
                                        <p class="text-sm text-secondary-foreground">No education information provided</p>
                                    </div>
                                {/if}
                            </div>
                        </div>

                        <!-- Languages & Skills Card -->
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Languages</h3>
                            </div>
                            <div class="kt-card-content">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Languages Section -->
                                    <div>
                                        {#if jobApplication?.languages && jobApplication.languages.length > 0}
                                            <div class="kt-scrollable-x-auto">
                                                <table class="kt-table kt-table-border">
                                                    <tbody>
                                                        {#each jobApplication.languages as language}
                                                            <tr>
                                                                <td>
                                                                    <span class="text-sm text-secondary-foreground">{language.name}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="kt-badge {getLanguageProficiencyBadgeClass(language.proficiency)}">
                                                                        {getLanguageProficiencyText(language.proficiency)}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        {/each}
                                                    </tbody>
                                                </table>
                                            </div>
                                        {:else}
                                            <p class="text-sm text-secondary-foreground">No languages specified</p>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
        </div>
    </div>
</AdminLayout> 