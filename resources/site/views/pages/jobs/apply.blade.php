{{--
    The job application wizard.

    INERT. The island mounts and validates client-side, but there is no
    JobApplication model and no POST route behind it — see the plan's decision 4
    and JobsController::apply(). The page is noindex and linked from nothing;
    standing the feature up is a backend plus two routes, with the front end and
    all nine locales of copy already in place.
--}}
@extends('site::layout')

@section('content')
    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h1 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand">
                @lang('jobs.apply.title')
            </h1>

            <hr class="mb-8 mt-2 border-line">

            {{-- data-island is the mount hook; site/islands.js loads the component
                 lazily and reads its props off data-props as JSON. --}}
            <div data-island="job-application" data-props="{{ json_encode([
                'csrf' => csrf_token(),
                // Null on both: there is nowhere to send this yet, and the island
                // is written to render without posting when they are absent.
                'validateUrl' => null,
                'submitUrl' => null,
                'jobPostingId' => $job?->id,
                'nationalities' => $countries->map(fn ($country) => [
                    'code' => $country->code,
                    'name' => $country->name,
                    'title' => $country->getTranslation('nationality', $site->locale(), true) ?: $country->name,
                ])->values(),
                't' => [
                    'personal' => __('jobs.apply.steps.personal'),
                    'education' => __('jobs.apply.steps.education'),
                    'experience' => __('jobs.apply.steps.experience'),
                    'languages' => __('jobs.apply.steps.languages'),
                    'skills' => __('jobs.apply.steps.skills'),
                    'documents' => __('jobs.apply.steps.documents'),
                    'review' => __('jobs.apply.steps.review'),
                    'firstName' => __('jobs.apply.fields.first_name'),
                    'lastName' => __('jobs.apply.fields.last_name'),
                    'email' => __('jobs.apply.fields.email'),
                    'phone' => __('jobs.apply.fields.phone'),
                    'nationality' => __('jobs.apply.fields.nationality'),
                    'address' => __('jobs.apply.fields.address'),
                    'next' => __('common.next'),
                    'previous' => __('common.previous'),
                    'submit' => __('jobs.apply.actions.submit'),
                    'addEducation' => __('jobs.apply.actions.add_education'),
                    'addExperience' => __('jobs.apply.actions.add_experience'),
                    'addLanguage' => __('jobs.apply.actions.add_language'),
                    'remove' => __('jobs.apply.actions.remove'),
                    'cv' => __('jobs.apply.documents.cv'),
                    'cvFormats' => __('jobs.apply.documents.cv_formats'),
                    'browse' => __('jobs.apply.documents.browse'),
                    'drag' => __('jobs.apply.documents.drag'),
                    'fixErrors' => __('jobs.apply.errors.fix'),
                    'networkError' => __('jobs.apply.errors.network'),
                    'success' => __('jobs.apply.success'),
                ],
            ]) }}"></div>
        </div>
    </section>
@endsection
