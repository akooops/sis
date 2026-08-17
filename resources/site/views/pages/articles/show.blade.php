@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $article->thumbnail_url,
        'title' => $article->getTranslation('title', $site->locale(), true) ?: $article->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
            <div class="grid gap-6 lg:grid-cols-3">

                <article class="card lg:col-span-2">
                    @if ($article->thumbnail_url)
                        <figure class="overlay h-[600px] shrink-0">
                            <img class="h-full w-full object-cover" src="{{ $article->thumbnail_url }}"
                                alt="{{ $article->getTranslation('title', $site->locale(), true) }}">
                        </figure>
                    @endif

                    <div class="card-body">
                        <h1 class="text-3xl font-black uppercase leading-[35px] text-brand">
                            {{ $article->getTranslation('title', $site->locale(), true) ?: $article->name }}
                        </h1>

                        <hr class="mb-4 mt-2 border-line">

                        {{-- Admin-authored rich text, same trust level as any other
                             page content — it is written in the admin editor and a
                             visitor can never reach it. --}}
                        @include('site::partials.content.content-styles', ['model' => $article])

                        {{-- id="page-content" is the scope hook the admin's own
                             CSS targets. See site::partials.content.content-styles. --}}
                        <div id="page-content" class="prose">
                            {!! $article->getTranslation('content', $site->locale(), true) !!}
                        </div>
                    </div>

                    <div class="card-footer">
                        <ul class="post-meta">
                            <li>
                                <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                <span>{{ $article->published_at?->translatedFormat('j M Y') }}</span>
                            </li>
                            @if ($article->category)
                                <li>
                                    <i class="uil uil-folder" aria-hidden="true"></i>
                                    <span>{{ $article->category->getTranslation('title', $site->locale(), true) ?: $article->category->name }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </article>

                <aside>
                    <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">@lang('site.articles.popular')</h4>

                    <ul>
                        @foreach ($popular as $item)
                            @php($url = route('web.site.articles.show', ['slug' => $item->slug]))
                            @php($label = $item->getTranslation('title', $site->locale(), true) ?: $item->name)

                            <li class="mt-4 flex gap-3 first:mt-0">
                                <figure class="h-[85px] w-[70px] shrink-0 overflow-hidden rounded-md">
                                    <a href="{{ $url }}">
                                        <img class="h-full w-full object-cover" src="{{ $item->thumbnail_url }}" alt="{{ $label }}"
                                            loading="lazy">
                                    </a>
                                </figure>
                    
                                <div class="min-w-0">
                                    <h6 class="mb-2">
                                        <a class="text-heading hover:text-brand" href="{{ $url }}">{{ $label }}</a>
                                    </h6>
                    
                                    <ul class="post-meta">
                                        <li>
                                            <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                            <span>{{ $item->published_at?->translatedFormat('j M Y') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                    </ul>                  
                </aside>
            </div>
        </div>
    </section>
@endsection
