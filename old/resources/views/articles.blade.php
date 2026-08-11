@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('articles'))

@section('content')

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
        <div class="grid gap-6 lg:grid-cols-3">

            <div class="lg:col-span-2">
                <div class="mb-8 grid auto-rows-fr gap-4 md:grid-cols-2">
                    @foreach ($articles as $article)
                        <article class="card media-card">
                            <figure class="overlay media-figure">
                                <a href="{{ route('article', ['slug' => $article->slug]) }}">
                                    <img src="{{ $article->thumbnailUrl }}"
                                        alt="{{ $article->getLocalTranslation('title') }}" />
                                </a>
                            </figure>

                            <div class="card-body">
                                <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                    <a class="hover:text-brand-soft" href="{{ route('article', ['slug' => $article->slug]) }}">
                                        {{ $article->getLocalTranslation('title') }}
                                    </a>
                                </h2>

                                <p class="mb-0">
                                    {{ $article->getLocalTranslation('description') }}
                                </p>
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
                    @endforeach
                </div>

                @include('partials.pagination', [
                    'pagination' => $pagination,
                    'route' => 'articles',
                    'params' => request()->only('search'),
                ])
            </div>

            <aside>
                <form class="mb-8" action="{{ route('articles') }}">
                    <input name="search" value="{{ request()->get('search') }}" type="search" class="input"
                        aria-label="{{ getLanguageKeyLocalTranslation('sidebar_popular_article_title') }}">
                </form>

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
