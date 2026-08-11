{{-- Careers listing. --}}
@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <form class="mb-8 flex gap-2" action="{{ route('web.site.jobs.index') }}">
                <input name="search" value="{{ $search }}" type="search" class="input"
                    placeholder="@lang('jobs.search.placeholder')" aria-label="@lang('jobs.search.placeholder')">
                <button type="submit" class="btn btn-sm shrink-0">@lang('jobs.search.button')</button>
            </form>

            @if ($jobs->isEmpty())
                <p class="text-muted">@lang('jobs.empty')</p>
            @else
                <div class="grid auto-rows-fr gap-4 md:grid-cols-2">
                    @foreach ($jobs as $job)
                        @php($url = route('web.site.jobs.show', ['slug' => $job->slug]))

                        <article class="card">
                            <div class="card-body">
                                <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                    <a class="hover:text-brand-soft" href="{{ $url }}">
                                        {{ $job->getTranslation('title', $site->locale(), true) ?: $job->name }}
                                    </a>
                                </h2>

                                {{-- Keyed by the enum value already on the model, so
                                     there is no match block here and a new value
                                     surfaces as a missing key rather than silently
                                     rendering nothing. --}}
                                <div class="mb-2 flex flex-wrap gap-2">
                                    <span class="badge">@lang('jobs.employment_type.'.$job->employment_type)</span>
                                    <span class="badge">@lang('jobs.work_mode.'.$job->work_mode)</span>
                                    @if ($job->education_level)
                                        <span class="badge">@lang('jobs.education_level.'.$job->education_level)</span>
                                    @endif
                                </div>

                                <p class="mb-0">{{ $job->getTranslation('description', $site->locale(), true) }}</p>
                            </div>

                            <div class="card-footer">
                                <ul class="post-meta">
                                    @if ($job->deadline_at)
                                        <li>
                                            <i class="uil uil-clock" aria-hidden="true"></i>
                                            <span>@lang('jobs.deadline'): {{ $job->deadline_at->translatedFormat('j M Y') }}</span>
                                        </li>
                                    @endif
                                    <li><a class="btn btn-sm" href="{{ $url }}">@lang('jobs.view')</a></li>
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    @include('site::partials.pagination', ['paginator' => $jobs])
                </div>
            @endif
        </div>
    </section>
@endsection
