@extends('facility.layouts.master')
@section('title', transOrDefault($event, 'title'))
@section('description', transOrDefault($event, 'description'))
@section('canonical', facilityRoute('event', ['slug' => $event->slug]))
@section('image', $event->thumbnailUrl)

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => transOrDefault($event, 'title'),
    'heroImage' => $event->thumbnailUrl,
])

<section class="wrapper">
    <div class="container py-3 py-md-5">
        <nav class="d-inline-block" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{ facilityRoute('home') }}">
                        {{ getLanguageKeyLocalTranslation('facility_nav_home') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{ facilityRoute('events') }}">
                        {{ getLanguageKeyLocalTranslation('facility_nav_events') }}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ transOrDefault($event, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($event, 'title') }}
        </h2>

        @if($event->starts_at)
            <ul class="post-meta d-flex mb-0 mt-2" data-aos="fade-up" data-aos-duration="1000">
                <li class="post-date">
                    <i class="uil uil-calendar-alt"></i>
                    <span>
                        {{ $event->starts_at->translatedFormat('d M Y H:i') }}
                        @if($event->ends_at)
                            - {{ $event->ends_at->translatedFormat('d M Y H:i') }}
                        @endif
                    </span>
                </li>
            </ul>
        @endif

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        <div class="w-100 page-content" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($event, 'content') !!}
        </div>

        <div class="d-flex gap-3 mt-8" data-aos="fade-up" data-aos-duration="1500">
            <a href="{{ facilityRoute('reserve') }}" class="btn btn-primary rounded">
                {{ getLanguageKeyLocalTranslation('facility_home_reserve_cta') }}
            </a>
        </div>
    </div>
</section>

@endsection
