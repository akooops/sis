@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $program->thumbnail_url,
        'title' => $program->getTranslation('title', $site->locale(), true) ?: $program->name,
    ])

    @include('site::partials.content.breadcrumb')

    @include('site::partials.content.body', [
        'title' => $program->getTranslation('title', $site->locale(), true) ?: $program->name,
        'subtitle' => $program->getTranslation('subtitle', $site->locale(), true)
            ?: $program->getTranslation('description', $site->locale(), true),
        'content' => $program->getTranslation('content', $site->locale(), true),
    ])

    @if ($program->streams->isNotEmpty())
        <section class="bg-surface">
            <div class="container py-14">
                {{-- Every stream is rendered, the inactive ones `hidden`, and the
                     tabs stay real links: site/tabs.js swaps panels in place,
                     and without it a click is still a normal page load on the
                     right tab. --}}
                <div class="tabs mb-6 flex flex-wrap gap-2" role="tablist" data-tabs>
                    @foreach ($program->streams as $stream)
                        @php($isActive = $activeStream && $activeStream->id === $stream->id)

                        <a class="tab-link {{ $isActive ? 'is-active' : '' }}"
                            href="{{ route('web.site.programs.show', ['slug' => $program->slug, 'stream' => $stream->slug]) }}"
                            id="stream-tab-{{ $stream->slug }}"
                            role="tab"
                            aria-controls="stream-panel-{{ $stream->slug }}"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}"
                            data-tab="{{ $stream->slug }}">
                            {{ $stream->getTranslation('title', $site->locale(), true) ?: $stream->name }}
                        </a>
                    @endforeach
                </div>

                @foreach ($program->streams as $stream)
                    <div class="stream"
                        id="stream-panel-{{ $stream->slug }}"
                        role="tabpanel"
                        aria-labelledby="stream-tab-{{ $stream->slug }}"
                        data-tab-panel="{{ $stream->slug }}"
                        @unless ($activeStream && $activeStream->id === $stream->id) hidden @endunless>

                        <div class="prose">
                            {!! $stream->getTranslation('content', $site->locale(), true) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($program->grades->isNotEmpty())
        <section>
            <div class="container py-14">
                <h2 class="mb-6 text-3xl font-black uppercase leading-[35px] text-brand">
                    @lang('site.programs.grades')
                </h2>
                
                <div class="flex flex-wrap gap-2">
                    @foreach ($program->grades as $grade)
                        <span class="chip">
                            {{ $grade->getTranslation('title', $site->locale(), true) ?: $grade->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
