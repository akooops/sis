@extends('facility.layouts.master')
@section('title', getLanguageKeyLocalTranslation('facility_nav_home'))
@section('description', transOrDefault($facility, 'description'))
@section('canonical', facilityRoute('home'))

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => transOrDefault($facility, 'title'),
    'heroImage' => $facility->thumbnailUrl,
])

<section class="wrapper page-content-section">
    <div class="container pt-10 pb-10">
        @php $facilityTagline = transOrDefault($facility, 'tagline'); @endphp
        @if($facilityTagline && !str_starts_with($facilityTagline, 'tagline.'))
            <h2 class="fs-15 text-uppercase text-primary mb-3" data-aos="fade-up" data-aos-duration="1000">
                {{ $facilityTagline }}
            </h2>
        @endif

        <h3 class="display-4 mb-5" data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($facility, 'title') }}
        </h3>

        <div class="w-100 page-content" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($facility, 'content') !!}
        </div>

        <div class="d-flex gap-3 mt-8" data-aos="fade-up" data-aos-duration="1500">
            <a href="{{ facilityRoute('reserve') }}" class="btn btn-primary rounded">
                {{ getLanguageKeyLocalTranslation('facility_home_reserve_cta') }}
            </a>
            <a href="{{ facilityRoute('contact') }}" class="btn btn-outline-primary rounded">
                {{ getLanguageKeyLocalTranslation('facility_nav_contact') }}
            </a>
        </div>
    </div>
</section>

@if($events->isNotEmpty())
<section class="wrapper bg-light">
    <div class="container py-12">
        <div class="d-flex justify-content-between align-items-center mb-6">
            <h2 class="mb-0">{{ getLanguageKeyLocalTranslation('facility_home_events_title') }}</h2>
            <a href="{{ facilityRoute('events') }}" class="text-primary">
                {{ getLanguageKeyLocalTranslation('facility_home_view_all') }}
            </a>
        </div>

        <div class="row gx-8 gy-4">
            @foreach ($events as $event)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <figure class="card-img-top overflow-hidden hover-scale">
                            <a href="{{ facilityRoute('event', ['slug' => $event->slug]) }}">
                                <img src="{{ $event->thumbnailUrl }}" alt="{{ transOrDefault($event, 'title') }}" />
                            </a>
                        </figure>
                        <div class="card-body p-4">
                            <h4 class="mb-2">
                                <a class="link-dark" href="{{ facilityRoute('event', ['slug' => $event->slug]) }}">
                                    {{ transOrDefault($event, 'title') }}
                                </a>
                            </h4>
                            <p class="truncate-3-lines mb-0">{{ transOrDefault($event, 'description') }}</p>
                            @if($event->starts_at)
                                <ul class="post-meta d-flex mb-0 mt-3">
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i>
                                        <span>{{ $event->starts_at->translatedFormat('d M Y') }}</span>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($articles->isNotEmpty())
<section class="wrapper">
    <div class="container py-12">
        <div class="d-flex justify-content-between align-items-center mb-6">
            <h2 class="mb-0">{{ getLanguageKeyLocalTranslation('facility_home_articles_title') }}</h2>
            <a href="{{ facilityRoute('articles') }}" class="text-primary">
                {{ getLanguageKeyLocalTranslation('facility_home_view_all') }}
            </a>
        </div>

        <div class="row gx-8 gy-4">
            @foreach ($articles as $article)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <figure class="card-img-top overflow-hidden hover-scale">
                            <a href="{{ facilityRoute('article', ['slug' => $article->slug]) }}">
                                <img src="{{ $article->thumbnailUrl }}" alt="{{ transOrDefault($article, 'title') }}" />
                            </a>
                        </figure>
                        <div class="card-body p-4">
                            <h4 class="mb-2">
                                <a class="link-dark" href="{{ facilityRoute('article', ['slug' => $article->slug]) }}">
                                    {{ transOrDefault($article, 'title') }}
                                </a>
                            </h4>
                            <p class="truncate-3-lines mb-0">{{ transOrDefault($article, 'description') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($albums->isNotEmpty())
<section class="wrapper bg-light">
    <div class="container py-12">
        <div class="d-flex justify-content-between align-items-center mb-6">
            <h2 class="mb-0">{{ getLanguageKeyLocalTranslation('facility_home_albums_title') }}</h2>
            <a href="{{ facilityRoute('albums') }}" class="text-primary">
                {{ getLanguageKeyLocalTranslation('facility_home_view_all') }}
            </a>
        </div>

        <div class="row gx-8 gy-4">
            @foreach ($albums as $album)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <figure class="card-img-top overflow-hidden hover-scale">
                            <a href="{{ facilityRoute('album', ['slug' => $album->slug]) }}">
                                <img src="{{ $album->thumbnailUrl }}" alt="{{ transOrDefault($album, 'title') }}" />
                            </a>
                        </figure>
                        <div class="card-body p-4">
                            <h4 class="mb-0">
                                <a class="link-dark" href="{{ facilityRoute('album', ['slug' => $album->slug]) }}">
                                    {{ transOrDefault($album, 'title') }}
                                </a>
                            </h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
