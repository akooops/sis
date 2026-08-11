@extends('layouts.master')
@section('title', $program->getLocalTranslation('title'))
@section('description', $program->getLocalTranslation('description'))
@section('canonical', route('program', ['slug' => $program->slug]))
@section('image', $program->thumbnailUrl)

@section('content')

@php
    $hasStreams = $program->has_streams && $program->streams->count() > 0;
@endphp

@include('partials.page-hero', [
    'image' => $program->thumbnailUrl,
    'title' => $program->getLocalTranslation('title'),
])

{{-- Sibling programmes act as the section navigation here. --}}
<section class="bg-surface">
    <div class="container flex justify-center overflow-x-auto py-8">
        <ul class="flex justify-center gap-6">
            @foreach ($programs as $menuProgram)
                <li class="whitespace-nowrap">
                    <a class="block py-2 text-base font-semibold uppercase {{ $program->id === $menuProgram->id ? 'text-danger' : 'text-brand hover:text-brand-soft' }}"
                        href="{{ route('program', ['slug' => $menuProgram->slug]) }}"
                        @if ($program->id === $menuProgram->id) aria-current="page" @endif>
                        {{ $menuProgram->getLocalTranslation('title') }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $program->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $program->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        @if ($hasStreams)
            <ul class="tabs" data-tabs data-aos="fade-up" data-aos-duration="2000" role="tablist">
                @foreach ($program->streams as $stream)
                    @php $isActive = optional($activeStream)->id === $stream->id; @endphp
                    <li role="presentation">
                        <button type="button"
                            class="tab-link {{ $isActive ? 'is-active' : '' }}"
                            data-toggle="tab"
                            data-target="#stream-{{ $stream->slug }}"
                            data-stream-slug="{{ $stream->slug }}"
                            role="tab"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}">
                            {{ $stream->getLocalTranslation('title') }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 md:mt-5" data-aos="fade-up" data-aos-duration="2000">
                @foreach ($program->streams as $stream)
                    <div id="stream-{{ $stream->slug }}" class="prose" role="tabpanel"
                        @unless (optional($activeStream)->id === $stream->id) hidden @endunless>
                        {!! $stream->getLocalTranslation('content') !!}
                    </div>
                @endforeach
            </div>
        @else
            <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
                {!! $program->getLocalTranslation('content') !!}
            </div>
        @endif
    </div>
</section>

@endsection

@section('script')
    @if ($hasStreams)
        <script>
            // Keep `?stream=` in sync with the open tab so the URL stays shareable.
            document.querySelectorAll('[data-toggle="tab"][data-stream-slug]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var url = new URL(window.location.href);
                    url.searchParams.set('stream', tab.dataset.streamSlug);
                    window.history.replaceState({}, '', url.toString());
                });
            });
        </script>
    @endif
@endsection
