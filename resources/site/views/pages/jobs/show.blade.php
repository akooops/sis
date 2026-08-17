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

                {{-- THE DESCRIPTION CARD. Both columns are cards, which is what
                     the old design did and what makes the page read as two
                     panels rather than a wall of text with a sidebar floating
                     beside it. --}}
                <div class="lg:col-span-2">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h2 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                                @lang('jobs.detail.description')
                            </h2>

                            @if ($description = $job->getTranslation('description', $site->locale(), true))
                                <div class="mb-4">
                                    <p>{{ $description }}</p>
                                </div>
                            @endif

                            @include('site::partials.content-styles', ['model' => $job])

                            {{-- id="page-content" is the scope hook the admin's own
                                 CSS targets. See site::partials.content-styles. --}}
                            <div id="page-content" class="prose">
                                {!! $job->getTranslation('content', $site->locale(), true) !!}
                            </div>

                            {{-- THE FORM LIVES IN THIS CARD, behind one button.

                                 Applying is the last thing you do after reading
                                 the role, so it belongs at the end of the thing
                                 you were reading — not in a separate slab below
                                 it that looks like a different page.

                                 data-toggle="collapse" is the site's own
                                 disclosure convention (see site/disclosure.js), so
                                 this needs no JavaScript of its own: the button
                                 gets aria-expanded, the panel gets aria-hidden and
                                 `.is-open`, and the height animates. --}}
                            @if ($presentation)
                                <div class="mt-8" id="apply">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        data-toggle="collapse"
                                        data-target="#apply-panel"
                                        aria-expanded="false"
                                        aria-controls="apply-panel"
                                    >
                                        {{-- BOTH labels ship; CSS shows whichever
                                             matches aria-expanded, which the
                                             disclosure handler already maintains.
                                             Swapping text in JS would mean a second
                                             listener duplicating what it does. --}}
                                        <span class="when-closed">
                                            <i class="uil uil-file-edit-alt" aria-hidden="true"></i>
                                            @lang('jobs.apply.cta')
                                        </span>
                                        <span class="when-open">
                                            <i class="uil uil-times" aria-hidden="true"></i>
                                            @lang('jobs.apply.abort')
                                        </span>
                                    </button>

                                    <div class="collapse-panel" id="apply-panel" aria-hidden="true">
                                        <div class="pt-6">
                                            @include('site::partials.form-embed', [
                                                'form' => $form,
                                                'presentation' => $presentation,
                                                'notice' => $notice,
                                                'presets' => $presets,
                                                'chrome' => false,
                                                'chooser' => true,
                                            ])
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Closed, blocked or unseeded: form-embed draws
                                     the reason, and there is nothing to open. --}}
                                <div class="mt-8">
                                    @include('site::partials.form-embed', [
                                        'form' => $form,
                                        'presentation' => null,
                                        'notice' => $notice,
                                    ])
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <aside>
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h4 class="mb-4 text-3xl font-black uppercase leading-[35px] text-brand">
                                @lang('jobs.detail.summary')
                            </h4>

                            {{-- LABEL + VALUE, not a bare value. "Full time" on its
                                 own tells the reader nothing about which fact it
                                 answers; the old design labelled every line and it
                                 is the reason this column scans. --}}
                            <ul class="job-facts">
                                @if ($address = $job->getTranslation('address', $site->locale(), true))
                                    <li><i class="uil uil-map-marker" aria-hidden="true"></i>
                                        <strong>@lang('jobs.detail.address'):</strong>
                                        <span>{{ $address }}</span></li>
                                @endif

                                @if ($job->experience_years)
                                    <li><i class="uil uil-clock" aria-hidden="true"></i>
                                        <strong>@lang('jobs.detail.experience'):</strong>
                                        <span>{{ $job->experience_years }}+</span></li>
                                @endif

                                <li><i class="uil uil-briefcase" aria-hidden="true"></i>
                                    <strong>@lang('jobs.detail.employment_type'):</strong>
                                    <span>@lang('jobs.employment_type.'.$job->employment_type)</span></li>

                                <li><i class="uil uil-building" aria-hidden="true"></i>
                                    <strong>@lang('jobs.detail.work_mode'):</strong>
                                    <span>@lang('jobs.work_mode.'.$job->work_mode)</span></li>

                                @if ($job->education_level)
                                    <li><i class="uil uil-graduation-cap" aria-hidden="true"></i>
                                        <strong>@lang('jobs.detail.education_level'):</strong>
                                        <span>@lang('jobs.education_level.'.$job->education_level)</span></li>
                                @endif

                                {{-- Skills live HERE, with the rest of the facts, not
                                     as their own section in the left column. They are
                                     one more thing about the vacancy. --}}
                                @if ($skills !== [])
                                    <li><i class="uil uil-star" aria-hidden="true"></i>
                                        <strong>@lang('jobs.detail.skills'):</strong>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($skills as $skill)
                                                <span class="chip">{{ $skill }}</span>
                                            @endforeach
                                        </div></li>
                                @endif

                                @if ($job->deadline_at)
                                    <li><i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                        <strong>@lang('jobs.deadline'):</strong>
                                        <span>{{ $job->deadline_at->translatedFormat('j M Y') }}</span></li>
                                @endif

                                @if ($job->published_at)
                                    <li><i class="uil uil-history" aria-hidden="true"></i>
                                        <strong>@lang('jobs.posted'):</strong>
                                        <span>{{ $job->published_at->translatedFormat('j M Y') }}</span></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

@endsection
