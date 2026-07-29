@extends('facility.layouts.master')
@section('title', transOrDefault($album, 'title'))
@section('description', transOrDefault($album, 'description'))
@section('canonical', facilityRoute('album', ['slug' => $album->slug]))
@section('image', $album->thumbnailUrl)

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => transOrDefault($album, 'title'),
    'heroImage' => $album->thumbnailUrl,
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
                    <a class="text-uppercase" href="{{ facilityRoute('albums') }}">
                        {{ getLanguageKeyLocalTranslation('facility_nav_albums') }}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ transOrDefault($album, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($album, 'title') }}
        </h2>

        <p data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($album, 'description') }}
        </p>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        @if($album->files->isEmpty())
            <p class="lead text-center py-10">{{ getLanguageKeyLocalTranslation('facility_empty_list_message') }}</p>
        @else
            <div class="row gx-4 gy-4" data-aos="fade-up" data-aos-duration="1500">
                @foreach ($album->files as $file)
                    <div class="col-6 col-md-4 col-lg-3">
                        <figure class="rounded hover-scale overflow-hidden mb-0">
                            <a href="{{ $file->url }}" target="_blank">
                                <img src="{{ $file->url }}" alt="{{ transOrDefault($album, 'title') }}" />
                            </a>
                        </figure>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
