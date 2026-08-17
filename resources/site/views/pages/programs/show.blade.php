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
                <h2 class="mb-6 text-3xl font-black uppercase leading-[35px] text-brand">
                    @lang('site.programs.streams')
                </h2>

                <div class="tabs mb-6 flex flex-wrap gap-2">
                    @foreach ($program->streams as $stream)
                        @php($isActive = $activeStream && $activeStream->id === $stream->id)

                        <a class="tab-link {{ $isActive ? 'is-active' : '' }}"
                            href="{{ route('web.site.programs.show', ['slug' => $program->slug, 'stream' => $stream->slug]) }}"
                            @if ($isActive) aria-current="page" @endif>
                            {{ $stream->getTranslation('title', $site->locale(), true) ?: $stream->name }}
                        </a>
                    @endforeach
                </div>

                @if ($activeStream)
                    <div class="stream">
                        <h3 class="stream-name">
                            {{ $activeStream->getTranslation('title', $site->locale(), true) ?: $activeStream->name }}
                        </h3>

                        @if ($subtitle = $activeStream->getTranslation('description', $site->locale(), true))
                            <p class="stream-label">{{ $subtitle }}</p>
                        @endif

                        <div class="prose">
                            {!! $activeStream->getTranslation('content', $site->locale(), true) !!}
                        </div>
                    </div>
                @endif
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
