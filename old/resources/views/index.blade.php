@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('index'))

@section('content')

@php
    $currentLanguage = getCurrentLanguage();

    $pathwayProgramSetting = getSetting('pathway_program_id');
    $pathwayProgram = $pathwayProgramSetting && !empty($pathwayProgramSetting->value)
        ? getProgramWithStreams($pathwayProgramSetting->value)
        : null;
@endphp

{{-- ---------------------------------------------------------------------
     Hero — full-viewport slider, one banner per slide.
     --------------------------------------------------------------------- --}}
<section class="relative">
    {{-- Full viewport height, so the banner artwork always fills the first screen. --}}
    <div class="swiper swiper-on-dark h-screen"
        data-swiper='{"loop": true, "spaceBetween": 0, "autoplay": {"delay": 60000, "disableOnInteraction": false}}'>

        <div class="swiper-wrapper h-screen">
            @foreach ($banners as $banner)
                <div class="swiper-slide scrim relative h-screen">
                    @if ($banner->video)
                        <video class="absolute inset-0 -z-10 h-full w-full object-cover" autoplay muted loop playsinline>
                            <source src="{{ $banner->videoUrl }}">
                            <img src="{{ $banner->thumbnailUrl }}" alt="{{ $banner->getLocalTranslation('title') }}">
                        </video>
                    @else
                        <div class="absolute inset-0 -z-10 bg-cover bg-center bg-no-repeat"
                            data-bg="{{ $banner->thumbnailUrl }}"></div>
                    @endif

                    {{-- z-3 lifts the copy above the scrim, which sits at z-1. --}}
                    <div class="container relative z-[3] h-full">
                        <div class="flex h-full items-end px-8 pb-24 lg:px-0">
                            <div class="grid w-full gap-4 lg:grid-cols-12 lg:gap-0 lg:px-14">
                                <div class="lg:col-span-8">
                                    <h2 class="hero-heading mb-0 text-6xl font-semibold uppercase leading-none text-paper lg:text-[55px] lg:leading-[52px]">
                                        {{ $banner->getLocalTranslation('title') }}
                                    </h2>
                                </div>

                                <div class="flex items-start lg:col-span-4 lg:px-24">
                                    <a href="{{ $banner->page ? route('page', ['slug' => $banner->page->slug]) : $banner->url }}"
                                        class="btn btn-on-dark px-24 py-1">
                                        <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                                        {{ $banner->getLocalTranslation('cta') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="swiper-controls">
            <div class="swiper-pagination bottom-[36px]"></div>
        </div>
    </div>
</section>

{{-- ---------------------------------------------------------------------
     Pathway — the two academic streams, side by side from lg up.
     --------------------------------------------------------------------- --}}
<section class="bg-surface">
    <div class="container px-8 py-24">
        <div class="mb-8 px-8 text-center">
            <h2 class="mb-2 text-6xl font-extrabold uppercase leading-10 text-brand md:text-[44px] md:leading-[52px] lg:text-9xl lg:leading-[71px] xl:text-[60px]"
                data-aos="fade-up" data-aos-duration="1000">
                {{ getLanguageKeyLocalTranslation('index_page_pathway_section_title') }}
            </h2>

            <p class="mb-0 mt-2 text-md leading-[26px] md:text-2xl md:leading-[30px] lg:text-3xl lg:leading-8"
                data-aos="fade-up" data-aos-duration="1500">
                {{ getLanguageKeyLocalTranslation('index_page_pathway_section_subtitle') }}
            </p>
        </div>

        @if ($pathwayProgram && $pathwayProgram->has_streams && $pathwayProgram->streams->count() > 0)
            <div class="grid gap-8 lg:grid-cols-2" data-aos="fade-up" data-aos-duration="1500">
                @foreach ($pathwayProgram->streams as $stream)
                    <div class="card">
                        <div class="card-body stream flex flex-col" style="--stream-color: {{ $stream->color }};">
                            <h3 class="mb-6 text-5xl font-black uppercase leading-10 sm:text-7xl sm:leading-10">
                                @if ($currentLanguage && $currentLanguage->is_rtl)
                                    <span class="stream-label">{{ getLanguageKeyLocalTranslation('index_page_pathway_stream_label') }}</span>
                                    <span class="stream-name">{{ $stream->getLocalTranslation('title') }}</span>
                                @else
                                    <span class="stream-name">{{ $stream->getLocalTranslation('title') }}</span>
                                    <span class="stream-label">{{ getLanguageKeyLocalTranslation('index_page_pathway_stream_label') }}</span>
                                @endif
                            </h3>

                            <p class="py-[6px] text-sm leading-8 text-ink lg:text-md">
                                {!! nl2br(e($stream->getLocalTranslation('description'))) !!}
                            </p>

                            <a href="{{ route('program', ['slug' => $pathwayProgram->slug]) }}?stream={{ $stream->slug }}"
                                class="btn btn-outline mt-auto w-full py-1 text-base">
                                <span class="mx-4">{{ $stream->getLocalTranslation('cta') }}</span>
                                <i class="uil uil-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@include('partials.divider')

{{-- ---------------------------------------------------------------------
     Academic levels — one slide per programme, scrim over the artwork.
     --------------------------------------------------------------------- --}}
<section class="bg-surface">
    <div class="container">
        <div class="mb-6 text-center">
            <h2 class="text-4xl font-black uppercase leading-[35px] text-brand lg:text-7xl lg:leading-[60px] xl:text-9xl"
                data-aos="fade-up" data-aos-duration="1000">
                {{ getLanguageKeyLocalTranslation('index_page_academics_levels_section_title') }}
            </h2>
        </div>
    </div>

    <div class="swiper swiper-on-dark"
        data-swiper='{"spaceBetween": 0, "breakpoints": {"768": {"slidesPerView": 2}, "1200": {"slidesPerView": 5}}}'
        data-aos="fade-up" data-aos-duration="2000">

        <div class="swiper-wrapper h-[400px] lg:h-[500px]">
            @foreach ($programs as $program)
                <div class="swiper-slide scrim scrim-brand relative">
                    <div class="absolute inset-0 -z-10 bg-cover bg-center bg-no-repeat"
                        data-bg="{{ $program->thumbnailUrl }}"></div>

                    {{-- z-3 lifts the copy above the scrim, which sits at z-1. --}}
                    <div class="container relative z-[3] h-full">
                        <div class="flex h-full items-end pb-8 lg:pb-14">
                            <div class="px-4 w-100">
                                <p class="mb-[10px] text-xl font-[420] uppercase leading-[33px] text-paper">
                                    {{ $program->getLocalTranslation('title') }}
                                </p>

                                @php
                                    $subtitle = $program->getLocalTranslation('subtitle');
                                @endphp
                                @if (!empty($subtitle) && trim(strip_tags($subtitle)) !== '' && $subtitle !== "\u{200e}")
                                    <h3 class="mb-4 text-5xl font-[450] uppercase leading-[33px] text-paper">
                                        {{ $subtitle }}
                                    </h3>
                                @endif

                                <a href="{{ route('program', ['slug' => $program->slug]) }}"
                                    class="btn btn-on-dark w-full border py-1">
                                    <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                                    {{ getLanguageKeyLocalTranslation('index_page_academics_levels_section_cta') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@include('partials.divider', ['spacing' => 'pt-8'])

{{-- ---------------------------------------------------------------------
     Latest news
     --------------------------------------------------------------------- --}}
<section class="mb-8 bg-surface">
    <div class="container pb-14">
        <div class="mb-6 text-center">
            <h2 class="text-4xl font-black uppercase leading-[35px] text-brand lg:text-7xl lg:leading-[60px] xl:text-9xl"
                data-aos="fade-up" data-aos-duration="1000">
                {{ getLanguageKeyLocalTranslation('index_page_articles_section_title') }}
            </h2>
        </div>

        <div class="swiper mb-8 px-8"
            data-swiper='{"spaceBetween": 10, "autoplay": {"delay": 7000, "disableOnInteraction": false}, "breakpoints": {"768": {"slidesPerView": 2}, "1200": {"slidesPerView": 3}}}'
            data-aos="fade-up" data-aos-duration="2000">

            <div class="swiper-wrapper">
                @foreach ($articles as $article)
                    <div class="swiper-slide bg-paper">
                        <figure class="overlay mb-5 h-[450px]">
                            <a href="{{ route('article', ['slug' => $article->slug]) }}">
                                <img class="h-full w-full object-cover" src="{{ $article->thumbnailUrl }}"
                                    alt="{{ $article->getLocalTranslation('title') }}" />
                            </a>
                        </figure>

                        <div class="px-8">
                            <h3 class="pb-2 pt-8">
                                <a class="text-xl font-normal uppercase text-brand lg:text-4xl"
                                    href="{{ route('article', ['slug' => $article->slug]) }}">
                                    {{ $article->getLocalTranslation('title') }}
                                </a>
                            </h3>

                            <span class="mb-6 inline-block text-7xl text-brand rtl:-scale-x-100" aria-hidden="true">⟶</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-center px-0 lg:px-8">
            <a href="{{ route('articles') }}" class="btn">
                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                {{ getLanguageKeyLocalTranslation('index_page_articles_section_cta') }}
            </a>
        </div>
    </div>
</section>

{{-- ---------------------------------------------------------------------
     Achievements — a vertical timeline, one slider per year.
     --------------------------------------------------------------------- --}}
<section>
    <div class="container mt-24">
        <div class="mb-6 text-center">
            <h2 class="text-4xl font-black uppercase leading-[35px] text-brand xl:text-9xl xl:leading-[60px]"
                data-aos="fade-up" data-aos-duration="1000">
                {{ getLanguageKeyLocalTranslation('index_page_achievements_section_title') }}
            </h2>
        </div>

        @if ($achievementsByYear->count() > 0)
            <div data-aos="fade-up" data-aos-duration="2000">
                @include('partials.achievement-timeline', ['achievementsByYear' => $achievementsByYear])
            </div>
        @endif

        <div class="mt-6 flex justify-center px-0 lg:px-8">
            <a href="{{ route('achievements') }}" class="btn">
                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                {{ getLanguageKeyLocalTranslation('index_page_achievements_section_cta') }}
            </a>
        </div>
    </div>
</section>

{{-- ---------------------------------------------------------------------
     Albums
     --------------------------------------------------------------------- --}}
<section class="overflow-hidden">
    <div class="container py-32">
        <div class="mb-6 text-center">
            <h2 class="text-4xl font-black uppercase leading-[35px] text-brand lg:text-7xl lg:leading-[60px] xl:text-9xl"
                data-aos="fade-up" data-aos-duration="1000">
                {{ getLanguageKeyLocalTranslation('index_page_albums_section_title') }}
            </h2>
        </div>

        <div class="swiper mb-8 !overflow-visible"
            data-swiper='{"spaceBetween": 30, "autoplay": {"delay": 2000, "disableOnInteraction": false}, "breakpoints": {"768": {"slidesPerView": 2}, "992": {"slidesPerView": 4}}}'
            data-aos="fade-up" data-aos-duration="2000">

            <div class="swiper-wrapper">
                @foreach ($albums as $album)
                    <div class="swiper-slide">
                        <figure class="overlay h-[450px]">
                            <a href="{{ route('album', ['slug' => $album->slug]) }}">
                                <img class="h-full w-full object-cover" src="{{ $album->thumbnailUrl }}"
                                    alt="{{ $album->getLocalTranslation('title') }}" />
                            </a>
                        </figure>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-center px-0 lg:px-8">
            <a href="{{ route('albums') }}" class="btn">
                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                {{ getLanguageKeyLocalTranslation('index_page_albums_section_cta') }}
            </a>
        </div>
    </div>
</section>

{{-- ---------------------------------------------------------------------
     Partners — a continuous marquee of logos.
     --------------------------------------------------------------------- --}}
<section class="mt-14 bg-mist">
    <div class="container border-t border-black/10 py-10">
        <div class="swiper"
            data-swiper='{"loop": true, "spaceBetween": 30, "speed": 5000, "allowTouchMove": false, "slidesPerView": 2, "autoplay": {"delay": 1, "disableOnInteraction": false}, "breakpoints": {"768": {"slidesPerView": 3}, "992": {"slidesPerView": 5}}}'>

            <div class="swiper-wrapper !ease-linear">
                @foreach ($partners as $partner)
                    <div class="swiper-slide flex h-16 items-center justify-center px-5">
                        @if ($partner->url)
                            <a class="flex h-full w-full items-center justify-center" href="{{ $partner->url }}"
                                target="_blank" rel="noopener noreferrer">
                                <img class="partner-logo" src="{{ $partner->logoUrl }}" alt="{{ $partner->name }}" />
                            </a>
                        @else
                            <img class="partner-logo" src="{{ $partner->logoUrl }}" alt="{{ $partner->name }}" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@endsection
