@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('jobs'))

@section('content')

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container py-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="2000">

        <div data-aos="fade-up" data-aos-duration="2500">
            <div class="mb-4">
                <a href="{{ route('jobs.apply') }}" class="btn btn-outline text-brand">
                    <i class="uil uil-file-alt" aria-hidden="true"></i>
                    {{ getLanguageKeyLocalTranslation('job_apply_now') }}
                </a>
            </div>

            <form action="{{ route('jobs') }}" method="GET" class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-3">
                    <input name="search" value="{{ request()->get('search') }}" type="search" class="input"
                        aria-label="{{ getLanguageKeyLocalTranslation('jobs_search_button') }}">
                </div>

                <button type="submit" class="btn w-full">
                    <i class="uil uil-search" aria-hidden="true"></i>
                    {{ getLanguageKeyLocalTranslation('jobs_search_button') }}
                </button>
            </form>

            <div class="mt-8 grid auto-rows-fr gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($jobs as $job)
                    <article class="card media-card transition-transform duration-200 hover:-translate-y-[3px]">
                        <div class="card-body">
                            <h2 class="mb-3 mt-1 text-3xl font-black uppercase leading-[35px] text-brand">
                                <a class="hover:text-brand-soft" href="{{ route('job', ['slug' => $job->slug]) }}">
                                    {{ $job->getLocalTranslation('title') }}
                                </a>
                            </h2>

                            <div class="mb-2 flex flex-wrap gap-2">
                                @if ($job->employment_type === 'full_time')
                                    <span class="badge bg-success">{{ getLanguageKeyLocalTranslation('jobs_full_time') }}</span>
                                @elseif ($job->employment_type === 'part_time')
                                    <span class="badge bg-warning">{{ getLanguageKeyLocalTranslation('jobs_part_time') }}</span>
                                @else
                                    <span class="badge bg-info">{{ getLanguageKeyLocalTranslation('jobs_internship') }}</span>
                                @endif

                                @if ($job->is_remote)
                                    <span class="badge">
                                        <i class="uil uil-laptop me-1" aria-hidden="true"></i>
                                        {{ getLanguageKeyLocalTranslation('jobs_remote') }}
                                    </span>
                                @endif
                            </div>

                            <p class="mb-4 line-clamp-3 text-muted">
                                {{ $job->getLocalTranslation('description') }}
                            </p>

                            @if ($job->getLocalTranslation('required_skills'))
                                @php
                                    $skills = array_filter(array_map('trim', explode(',', $job->getLocalTranslation('required_skills'))));
                                    $displaySkills = array_slice($skills, 0, 3);
                                @endphp

                                @if (count($displaySkills) > 0)
                                    <div class="mb-2 flex flex-wrap gap-1">
                                        @foreach ($displaySkills as $skill)
                                            <span class="badge bg-mist text-ink">{{ $skill }}</span>
                                        @endforeach

                                        @if (count($skills) > 3)
                                            <span class="badge bg-mist text-muted">+{{ count($skills) - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            @if ($job->application_deadline)
                                <p class="mb-2 mt-6 text-sm text-muted">
                                    <i class="uil uil-calendar-alt me-1" aria-hidden="true"></i>
                                    {{ getLanguageKeyLocalTranslation('jobs_deadline') }}: {{ $job->application_deadline }}
                                </p>
                            @endif

                            <a href="{{ route('job', ['slug' => $job->slug]) }}" class="btn btn-sm">
                                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                                {{ getLanguageKeyLocalTranslation('jobs_view_details') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                @include('partials.pagination', [
                    'pagination' => $pagination,
                    'route' => 'jobs',
                    'params' => request()->only('search'),
                ])
            </div>
        </div>
    </div>
</section>

@endsection
