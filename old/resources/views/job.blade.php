@extends('layouts.master')

@php
    $isGeneralApplication = !isset($job) || !$job;
    $pageTitle = $isGeneralApplication
        ? getLanguageKeyLocalTranslation('job_application_title')
        : $job->getLocalTranslation('title');
    $pageDescription = $isGeneralApplication
        ? getLanguageKeyLocalTranslation('job_application_title')
        : $job->getLocalTranslation('description');
    $pageCanonical = $isGeneralApplication
        ? route('jobs.apply')
        : route('job', ['slug' => $job->slug]);
    $bannerImage = $isGeneralApplication
        ? ($jobsPage->thumbnailUrl ?? '/assets/img/photos/bg1.jpg')
        : $job->thumbnailUrl;

    $isExpired = !$isGeneralApplication
        && $job->application_deadline
        && $job->application_deadline < now();

    // Copy and endpoints for the application island. Labels resolve here so the
    // DB translations remain the single source of truth.
    $applicationProps = [
        'csrf' => csrf_token(),
        'validateUrl' => route('job-applications.validate'),
        'submitUrl' => $isGeneralApplication
            ? route('job-applications.store-general')
            : route('job-applications.store', $job->id),
        'jobPostingId' => $isGeneralApplication ? null : $job->id,
        'nationalities' => $nationalities ?? [],
        't' => [
            'application_title' => getLanguageKeyLocalTranslation('job_application_title'),
            'close_form' => getLanguageKeyLocalTranslation('job_close_form'),
            'step_personal' => getLanguageKeyLocalTranslation('job_step_personal'),
            'step_education' => getLanguageKeyLocalTranslation('job_step_education'),
            'step_experience' => getLanguageKeyLocalTranslation('job_step_experience'),
            'step_languages' => getLanguageKeyLocalTranslation('job_step_languages'),
            'step_skills' => getLanguageKeyLocalTranslation('job_step_skills'),
            'step_documents' => getLanguageKeyLocalTranslation('job_step_documents'),
            'step_review' => getLanguageKeyLocalTranslation('job_step_review'),
            'personal_info' => getLanguageKeyLocalTranslation('job_personal_info'),
            'first_name' => getLanguageKeyLocalTranslation('job_first_name'),
            'last_name' => getLanguageKeyLocalTranslation('job_last_name'),
            'email' => getLanguageKeyLocalTranslation('job_email'),
            'phone' => getLanguageKeyLocalTranslation('job_phone'),
            'nationality' => getLanguageKeyLocalTranslation('job_nationality'),
            'address' => getLanguageKeyLocalTranslation('job_address'),
            'education' => getLanguageKeyLocalTranslation('job_education'),
            'institution' => getLanguageKeyLocalTranslation('job_institution'),
            'degree' => getLanguageKeyLocalTranslation('job_degree'),
            'field_of_study' => getLanguageKeyLocalTranslation('job_field_study'),
            'start_year' => getLanguageKeyLocalTranslation('job_start_year'),
            'end_year' => getLanguageKeyLocalTranslation('job_end_year'),
            'description' => getLanguageKeyLocalTranslation('job_description'),
            'add_education' => getLanguageKeyLocalTranslation('job_add_education'),
            'work_experience' => getLanguageKeyLocalTranslation('job_work_experience'),
            'company_name' => getLanguageKeyLocalTranslation('job_company_name'),
            'job_title' => getLanguageKeyLocalTranslation('job_job_title'),
            'current_job' => getLanguageKeyLocalTranslation('job_current_job'),
            'job_description' => getLanguageKeyLocalTranslation('job_job_description'),
            'add_experience' => getLanguageKeyLocalTranslation('job_add_experience'),
            'languages' => getLanguageKeyLocalTranslation('job_languages'),
            'language_name' => getLanguageKeyLocalTranslation('job_language_name'),
            'select_proficiency' => getLanguageKeyLocalTranslation('job_select_proficiency'),
            'basic' => getLanguageKeyLocalTranslation('job_basic'),
            'intermediate' => getLanguageKeyLocalTranslation('job_intermediate'),
            'advanced' => getLanguageKeyLocalTranslation('job_advanced'),
            'native' => getLanguageKeyLocalTranslation('job_native'),
            'add_language' => getLanguageKeyLocalTranslation('job_add_language'),
            'skills' => getLanguageKeyLocalTranslation('job_skills'),
            'add_skills' => getLanguageKeyLocalTranslation('job_add_skills'),
            'no_skills' => getLanguageKeyLocalTranslation('job_no_skills'),
            'documents' => getLanguageKeyLocalTranslation('job_documents'),
            'cv_required' => getLanguageKeyLocalTranslation('job_cv_required'),
            'cv_formats' => getLanguageKeyLocalTranslation('job_cv_formats'),
            'review_submit' => getLanguageKeyLocalTranslation('job_review_submit'),
            'name' => getLanguageKeyLocalTranslation('job_name'),
            'previous' => getLanguageKeyLocalTranslation('job_previous'),
            'next' => getLanguageKeyLocalTranslation('job_next'),
            'submit_application' => getLanguageKeyLocalTranslation('job_submit_application'),
            'present' => getLanguageKeyLocalTranslation('job_present') ?: 'Present',
            'fix_errors' => getLanguageKeyLocalTranslation('job_fix_errors')
                ?: 'Please fix the errors before continuing',
            'fix_documents' => getLanguageKeyLocalTranslation('job_fix_documents')
                ?: 'Please fix the document errors before continuing',
            'network_error' => getLanguageKeyLocalTranslation('job_network_error')
                ?: 'Network error. Please check your connection.',
            'cv_too_large' => getLanguageKeyLocalTranslation('job_cv_too_large')
                ?: 'File size must be less than 5MB',
            'cv_wrong_type' => getLanguageKeyLocalTranslation('job_cv_wrong_type')
                ?: 'CV must be PDF, DOC, or DOCX format',
            'submit_success' => getLanguageKeyLocalTranslation('job_submit_success')
                ?: 'Application submitted successfully!',
            'submit_error' => getLanguageKeyLocalTranslation('job_submit_error')
                ?: 'Error submitting application',
        ],
    ];
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)
@section('canonical', $pageCanonical)

@section('content')

@include('partials.page-hero', ['image' => $bannerImage, 'title' => $pageTitle])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_jobs_page_title'),
        'url' => route('jobs'),
    ]],
    'current' => $pageTitle,
])

<section data-aos="fade-up" data-aos-duration="1000">
    <div class="container py-14 md:py-10">
        <div class="grid gap-6 lg:grid-cols-3">

            <div class="lg:col-span-2">
                {{-- Server-rendered so the vacancy stays indexable. The island
                     hides this panel while the application form is open. --}}
                <div id="job-description" class="card shadow-float">
                    <div class="card-body">
                        <h2 class="mb-3 text-3xl font-black uppercase text-brand">
                            {{ getLanguageKeyLocalTranslation('job_description_title') }}
                        </h2>

                        @if (!$isGeneralApplication)
                            <p class="mb-4">{{ $job->getLocalTranslation('description') }}</p>

                            <div class="prose mb-6">{!! $job->getLocalTranslation('content') !!}</div>
                        @else
                            <p class="mb-6">{{ getLanguageKeyLocalTranslation('job_application_title') }}</p>
                        @endif

                        @if ($isExpired)
                            <div class="alert alert-warning flex items-center gap-2">
                                <i class="uil uil-exclamation-triangle" aria-hidden="true"></i>
                                {{ getLanguageKeyLocalTranslation('job_application_expired') }}
                            </div>
                        @else
                            <button type="button" class="btn self-start" data-apply-now>
                                <i class="uil uil-envelope" aria-hidden="true"></i>
                                {{ getLanguageKeyLocalTranslation('job_apply_now') }}
                            </button>
                        @endif
                    </div>
                </div>

                @unless ($isExpired)
                    <div data-island="job-application" data-props="{{ json_encode($applicationProps) }}"></div>
                @endunless
            </div>

            {{-- Vacancy facts --}}
            @if (!$isGeneralApplication)
                <aside>
                    <div class="card mb-4 shadow-float">
                        <div class="card-body">
                            <h2 class="mb-4 text-3xl font-black uppercase text-brand">
                                {{ getLanguageKeyLocalTranslation('job_details_title') }}
                            </h2>

                            <ul class="flex flex-col gap-3">
                                @if ($job->required_years_of_experience)
                                    <li>
                                        <i class="uil uil-clock me-2 text-brand" aria-hidden="true"></i>
                                        <strong>{{ getLanguageKeyLocalTranslation('job_experience_required') }}:</strong>
                                        <span class="ms-2">{{ $job->required_years_of_experience }}+ {{ getLanguageKeyLocalTranslation('job_years') }}</span>
                                    </li>
                                @endif

                                <li>
                                    <i class="uil uil-briefcase me-2 text-brand" aria-hidden="true"></i>
                                    <strong>{{ getLanguageKeyLocalTranslation('job_employment_type') }}:</strong>
                                    <span class="ms-2">
                                        @if ($job->employment_type === 'full_time')
                                            {{ getLanguageKeyLocalTranslation('jobs_full_time') }}
                                        @elseif ($job->employment_type === 'part_time')
                                            {{ getLanguageKeyLocalTranslation('jobs_part_time') }}
                                        @else
                                            {{ getLanguageKeyLocalTranslation('jobs_internship') }}
                                        @endif
                                    </span>
                                </li>

                                <li>
                                    <i class="uil uil-{{ $job->is_remote ? 'laptop' : 'building' }} me-2 text-brand" aria-hidden="true"></i>
                                    <strong>{{ getLanguageKeyLocalTranslation('job_work_type') }}:</strong>
                                    <span class="ms-2">
                                        {{ $job->is_remote
                                            ? getLanguageKeyLocalTranslation('jobs_remote')
                                            : getLanguageKeyLocalTranslation('jobs_onsite') }}
                                    </span>
                                </li>

                                <li>
                                    <i class="uil uil-users-alt me-2 text-brand" aria-hidden="true"></i>
                                    <strong>{{ getLanguageKeyLocalTranslation('job_positions_available') }}:</strong>
                                    <span class="ms-2">{{ $job->number_of_positions }}</span>
                                </li>

                                @if ($job->getLocalTranslation('required_skills'))
                                    <li>
                                        <i class="uil uil-star me-2 text-brand" aria-hidden="true"></i>
                                        <strong>{{ getLanguageKeyLocalTranslation('job_skills_title') }}:</strong>

                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach (array_filter(array_map('trim', explode(',', $job->getLocalTranslation('required_skills')))) as $skill)
                                                <span class="badge bg-mist text-ink">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </li>
                                @endif

                                @if ($job->application_deadline)
                                    <li>
                                        <i class="uil uil-calendar-alt me-2 text-brand" aria-hidden="true"></i>
                                        <strong>{{ getLanguageKeyLocalTranslation('job_application_deadline') }}:</strong>
                                        <span class="ms-2">{{ $job->application_deadline }}</span>
                                    </li>
                                @endif

                                <li>
                                    <i class="uil uil-calendar-plus me-2 text-brand" aria-hidden="true"></i>
                                    <strong>{{ getLanguageKeyLocalTranslation('job_posted_date') }}:</strong>
                                    <span class="ms-2">{{ $job->created_at }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>
            @endif
        </div>
    </div>
</section>

@endsection
