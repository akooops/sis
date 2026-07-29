@extends('facility.layouts.master')
@section('title', transOrDefault($article, 'title'))
@section('description', transOrDefault($article, 'description'))
@section('canonical', facilityRoute('article', ['slug' => $article->slug]))
@section('image', $article->thumbnailUrl)

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => transOrDefault($article, 'title'),
    'heroImage' => $article->thumbnailUrl,
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
                    <a class="text-uppercase" href="{{ facilityRoute('articles') }}">
                        {{ getLanguageKeyLocalTranslation('facility_nav_articles') }}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ transOrDefault($article, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($article, 'title') }}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        <div class="w-100 page-content" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($article, 'content') !!}
        </div>

        @if($otherArticles->isNotEmpty())
            <hr class="mt-8 mb-6">

            <h3 class="mb-4">{{ getLanguageKeyLocalTranslation('facility_article_more_title') }}</h3>

            <div class="row gx-8 gy-4">
                @foreach ($otherArticles as $otherArticle)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <figure class="card-img-top overflow-hidden hover-scale">
                                <a href="{{ facilityRoute('article', ['slug' => $otherArticle->slug]) }}">
                                    <img src="{{ $otherArticle->thumbnailUrl }}" alt="{{ transOrDefault($otherArticle, 'title') }}" />
                                </a>
                            </figure>
                            <div class="card-body p-4">
                                <h4 class="mb-0">
                                    <a class="link-dark" href="{{ facilityRoute('article', ['slug' => $otherArticle->slug]) }}">
                                        {{ transOrDefault($otherArticle, 'title') }}
                                    </a>
                                </h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
