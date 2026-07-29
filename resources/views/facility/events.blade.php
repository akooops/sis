@extends('facility.layouts.master')
@section('title', getLanguageKeyLocalTranslation('facility_nav_events'))
@section('description', transOrDefault($facility, 'description'))
@section('canonical', facilityRoute('events'))

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => getLanguageKeyLocalTranslation('facility_nav_events'),
    'heroImage' => $facility->thumbnailUrl,
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
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ getLanguageKeyLocalTranslation('facility_nav_events') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        @if($events->isEmpty())
            <p class="lead text-center py-10">{{ getLanguageKeyLocalTranslation('facility_empty_list_message') }}</p>
        @else
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
        @endif
    </div>
</section>

@endsection
