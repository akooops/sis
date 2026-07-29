@extends('facility.layouts.master')
@section('title', getLanguageKeyLocalTranslation('facility_nav_articles'))
@section('description', transOrDefault($facility, 'description'))
@section('canonical', facilityRoute('articles'))

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => getLanguageKeyLocalTranslation('facility_nav_articles'),
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
                    {{ getLanguageKeyLocalTranslation('facility_nav_articles') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        @if($articles->isEmpty())
            <p class="lead text-center py-10">{{ getLanguageKeyLocalTranslation('facility_empty_list_message') }}</p>
        @else
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

            @include('facility.partials.pagination', ['pagination' => $pagination, 'routeName' => 'articles'])
        @endif
    </div>
</section>

@endsection
