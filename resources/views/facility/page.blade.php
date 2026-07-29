@extends('facility.layouts.master')
@section('title', transOrDefault($page, 'title'))
@section('description', transOrDefault($page, 'description'))
@section('canonical', facilityRoute('page', ['slug' => $page->slug]))
@section('image', $page->thumbnailUrl)

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => transOrDefault($page, 'title'),
    'heroImage' => $page->thumbnailUrl,
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
                    {{ transOrDefault($page, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($page, 'title') }}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        <div class="w-100 page-content" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($page, 'content') !!}
        </div>
    </div>
</section>

@endsection
