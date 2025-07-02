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
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Job Application Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Application Details</h1>
                    <p class="text-sm text-secondary-foreground">
                        {jobApplication?.first_name} {jobApplication?.last_name} - {jobApplication?.job_posting?.getLocalTranslation?.('title') || jobApplication?.job_posting?.name}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {#if jobApplication?.ai_score}
                        <span class="kt-badge {getAiScoreBadgeClass(jobApplication.ai_score)}">
                            AI Score: {getAiScoreText(jobApplication.ai_score)}
                        </span>
                    {/if}
                    <a href="{route('admin.job-applications.index', { jobPosting: jobApplication?.job_posting?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Applications
                    </a>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Personal Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="grid gap-4">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Full Name</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {jobApplication?.first_name} {jobApplication?.last_name}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Email Address</h4>
                                <a href="mailto:{jobApplication?.email}" class="text-sm text-primary hover:text-primary-dark">
                                    {jobApplication?.email}
                                </a>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Phone Number</h4>
                                <a href="tel:{jobApplication?.phone}" class="text-sm text-primary hover:text-primary-dark">
                                    {jobApplication?.phone}
                                </a>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Nationality</h4>
                                <p class="text-sm text-secondary-foreground">{jobApplication?.nationality}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Address</h4>
                                <p class="text-sm text-secondary-foreground">{jobApplication?.address}</p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">Application Date</h4>
                                <p class="text-sm text-secondary-foreground">
                                    {jobApplication?.created_at ? new Date(jobApplication.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Education Background Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Education Background</h4>
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

            <!-- Work Experience Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Work Experience</h4>
                </div>
                <div class="kt-card-content">
                    {#if jobApplication?.experiences && jobApplication.experiences.length > 0}
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Company</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each jobApplication.experiences as experience}
                                        <tr>
                                            <td>
                                                <h6 class="text-sm font-medium text-mono mb-1">{experience.job_title}</h6>
                                            </td>
                                            <td>
                                                <span class="text-sm text-secondary-foreground">{experience.company_name}</span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    {experience.start_year} - {experience.is_current ? 'Present' : experience.end_year}
                                                    ({calculateYears(experience.start_year, experience.end_year, experience.is_current)})
                                                </span>
                                            </td>
                                            <td>
                                                {#if experience.is_current}
                                                    <span class="kt-badge kt-badge-success">Current</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-secondary">Previous</span>
                                                {/if}
                                            </td>
                                        </tr>
                                        {#if experience.description}
                                            <tr>
                                                <td colspan="4">
                                                    <small class="text-sm text-secondary-foreground">{experience.description}</small>
                                                </td>
                                            </tr>
                                        {/if}
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <div class="text-center py-8">
                            <p class="text-sm text-secondary-foreground">No work experience provided</p>
                        </div>
                    {/if}
                </div>
            </div>

            <!-- Languages & Skills Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Languages & Skills</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Languages Section -->
                        <div>
                            <h5 class="text-sm font-semibold text-mono mb-4">Languages</h5>
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

                        <!-- Skills Section -->
                        <div>
                            <h5 class="text-sm font-semibold text-mono mb-4">Skills</h5>
                            {#if getSkillsArray(jobApplication?.skills).length > 0}
                                <div class="flex flex-wrap gap-2">
                                    {#each getSkillsArray(jobApplication?.skills) as skill}
                                        <span class="kt-badge kt-badge-primary">{skill}</span>
                                    {/each}
                                </div>
                            {:else}
                                <p class="text-sm text-secondary-foreground">No skills specified</p>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Additional Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Documents & Additional Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- CV/Resume Section -->
                        <div>
                            <h5 class="text-sm font-semibold text-mono mb-4">CV/Resume</h5>
                            {#if jobApplication?.cv}
                                <div class="flex items-center p-4 border border-border rounded-lg">
                                    <div class="flex-shrink-0">
                                        <i class="ki-filled ki-file text-danger text-2xl"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-sm font-medium text-mono mb-1">CV/Resume</h6>
                                        <p class="text-xs text-secondary-foreground">
                                            Uploaded: {jobApplication?.created_at ? new Date(jobApplication.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric'
                                            }) : 'N/A'}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href={jobApplication.cv?.url} target="_blank" class="kt-btn kt-btn-sm kt-btn-outline kt-btn-primary">
                                            <i class="ki-filled ki-download text-xs"></i>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            {:else}
                                <p class="text-sm text-secondary-foreground">No CV uploaded</p>
                            {/if}
                        </div>

                        <!-- Application Analytics Section -->
                        <div>
                            <h5 class="text-sm font-semibold text-mono mb-4">Application Analytics</h5>
                            <div class="border border-border rounded-lg p-4">
                                {#if jobApplication?.ai_score}
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-sm text-secondary-foreground">AI Score:</span>
                                        <span class="kt-badge {getAiScoreBadgeClass(jobApplication.ai_score)}">
                                            {getAiScoreText(jobApplication.ai_score)}
                                        </span>
                                    </div>
                                    {#if jobApplication?.ai_scored_at}
                                        <small class="text-xs text-secondary-foreground">
                                            Scored: {new Date(jobApplication.ai_scored_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            })}
                                        </small>
                                    {/if}
                                {:else}
                                    <p class="text-sm text-secondary-foreground mb-0">AI scoring pending</p>
                                {/if}
                                
                                <div class="mt-3">
                                    <small class="text-xs text-secondary-foreground">
                                        Applied: {jobApplication?.created_at ? new Date(jobApplication.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) : 'N/A'}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 