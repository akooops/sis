@extends('layouts.master')
@section('title', $article->getLocalTranslation('title'))
@section('description', $article->getLocalTranslation('description'))
@section('canonical', route('article', ['slug' => $article->slug]))
@section('image', $article->thumbnailUrl)

@section('content')

@include('partials.page-hero', [
    'image' => $article->thumbnailUrl,
    'title' => $article->getLocalTranslation('title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_articles_page_title'),
        'url' => route('articles'),
    ]],
    'current' => $article->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
        <div class="grid gap-6 lg:grid-cols-3">

            <article class="card lg:col-span-2">
                <figure class="overlay h-[600px] shrink-0">
                    <img class="h-full w-full object-cover" src="{{ $article->thumbnailUrl }}"
                        alt="{{ $article->getLocalTranslation('title') }}">
                </figure>

                <div class="card-body">
                    <h2 class="text-3xl font-black uppercase leading-[35px] text-brand">
                        {{ $article->getLocalTranslation('title') }}
                    </h2>

                    <hr class="mb-4 mt-2 border-line">

                    <div class="prose">
                        {!! $article->getLocalTranslation('content') !!}
                    </div>
                </div>

                <div class="card-footer">
                    <ul class="post-meta">
                        <li>
                            <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                            <span>{{ $article->created_at->format('Y-m-d') }}</span>
                        </li>
                    </ul>
                </div>
            </article>

            <aside>
                @include('partials.media-list', [
                    'title' => getLanguageKeyLocalTranslation('sidebar_popular_article_title'),
                    'items' => $popularArticles,
                    'route' => 'article',
                ])
            </aside>
        </div>
    </div>
</section>

@endsection
