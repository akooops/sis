{{-- One vacancy. --}}
@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $job->thumbnail_url,
        'title' => $job->getTranslation('title', $site->locale(), true) ?: $job->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="grid gap-6 lg:grid-cols-3">

                <div class="lg:col-span-2">
                    <h2 class="mb-4 text-3xl font-black uppercase leading-[35px] text-brand">
                        @lang('jobs.detail.description')
                    </h2>

                    @include('site::partials.content-styles', ['model' => $job])

                    {{-- id="page-content" is the scope hook the admin's own CSS
                         targets. See site::partials.content-styles. --}}
                    <div id="page-content" class="prose">
                        {!! $job->getTranslation('content', $site->locale(), true) !!}
                    </div>

                    @if ($skills !== [])
                        <h2 class="mb-4 mt-8 text-3xl font-black uppercase leading-[35px] text-brand">
                            @lang('jobs.detail.skills')
                        </h2>

                        {{-- `skills` is a ;;;-joined translatable STRING on this
                             model, not an array — splitSkills is the only correct
                             reader and the controller already applied it. --}}
                        <div class="flex flex-wrap gap-2">
                            @foreach ($skills as $skill)
                                <span class="chip">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside>
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                                @lang('jobs.detail.summary')
                            </h4>

                            <ul class="post-meta flex-col items-start gap-2">
                                <li><i class="uil uil-briefcase" aria-hidden="true"></i>
                                    <span>@lang('jobs.employment_type.'.$job->employment_type)</span></li>
                                <li><i class="uil uil-map-marker" aria-hidden="true"></i>
                                    <span>@lang('jobs.work_mode.'.$job->work_mode)</span></li>

                                @if ($job->education_level)
                                    <li><i class="uil uil-graduation-cap" aria-hidden="true"></i>
                                        <span>@lang('jobs.education_level.'.$job->education_level)</span></li>
                                @endif

                                @if ($job->published_at)
                                    <li><i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                        <span>@lang('jobs.posted'): {{ $job->published_at->translatedFormat('j M Y') }}</span></li>
                                @endif

                                @if ($job->deadline_at)
                                    <li><i class="uil uil-clock" aria-hidden="true"></i>
                                        <span>@lang('jobs.deadline'): {{ $job->deadline_at->translatedFormat('j M Y') }}</span></li>
                                @endif

                                @if ($address = $job->getTranslation('address', $site->locale(), true))
                                    <li><i class="uil uil-building" aria-hidden="true"></i><span>{{ $address }}</span></li>
                                @endif
                            </ul>

                            {{-- The wizard is inert in this build — no POST route and
                                 no JobApplication model — so the page is reachable
                                 but nothing here links to it. See JobsController. --}}
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
