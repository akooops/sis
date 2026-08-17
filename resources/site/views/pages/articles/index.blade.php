@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
            <div class="grid gap-6 lg:grid-cols-3">

                <div class="lg:col-span-2">
                    @if ($articles->isEmpty())
                        <p class="text-muted">@lang('site.articles.empty')</p>
                    @else
                        <div class="mb-8 grid auto-rows-fr gap-4 md:grid-cols-2">
                            @foreach ($articles as $article)
                                @php($url = route('web.site.articles.show', ['slug' => $article->slug]))

                                <article class="card media-card">
                                    <figure class="overlay media-figure">
                                        <a href="{{ $url }}">
                                            <img src="{{ $article->thumbnail_url }}"
                                                alt="{{ $article->getTranslation('title', $site->locale(), true) }}"
                                                loading="lazy">
                                        </a>
                                    </figure>

                                    <div class="card-body">
                                        @if ($article->category)
                                            {{-- The chip wears the category's own
                                                 colour; .badge falls back to brand
                                                 when the row has none. --}}
                                            <span class="badge mb-2" style="--badge-color: {{ $article->category->color }}">
                                                {{ $article->category->getTranslation('title', $site->locale(), true) ?: $article->category->name }}
                                            </span>
                                        @endif

                                        <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                            <a class="hover:text-brand-soft" href="{{ $url }}">
                                                {{ $article->getTranslation('title', $site->locale(), true) ?: $article->name }}
                                            </a>
                                        </h2>

                                        <p class="mb-0">{{ $article->getTranslation('description', $site->locale(), true) }}</p>
                                    </div>

                                    <div class="card-footer">
                                        <ul class="post-meta">
                                            <li>
                                                <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                                <span>{{ $article->published_at?->translatedFormat('j M Y') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @include('site::partials.ui.pagination', ['paginator' => $articles])
                    @endif
                </div>

                <aside>
                    <form class="mb-8" action="{{ route('web.site.articles.index') }}">
                        <input name="search" value="{{ $search }}" type="search" class="input"
                            placeholder="@lang('site.articles.filters.search')" aria-label="@lang('site.articles.filters.search')">
                    </form>

                    @if ($categories->isNotEmpty())
                        <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                            @lang('site.articles.filters.categories')
                        </h4>

                        {{-- Coloured chips rather than a stacked list of links, so
                             the filter reads the same as the chip printed on each
                             article card. The selected one is ringed in its OWN
                             colour instead of recoloured, which would have
                             detached it from the category it names.

                             A row, not a column: the chips are short and their
                             colour is the thing being scanned. --}}
                        <ul class="mb-8 flex flex-wrap gap-2">
                            @foreach ($categories as $item)
                                @php($isActive = $category === $item->code)

                                <li>
                                    {{-- The active chip links back to the unfiltered
                                         listing, so clicking it clears the filter. --}}
                                    <a class="badge {{ $isActive ? 'is-active' : '' }}"
                                        style="--badge-color: {{ $item->color }}"
                                        href="{{ $isActive ? route('web.site.articles.index') : route('web.site.articles.index', ['category' => $item->code]) }}"
                                        @if ($isActive) aria-current="true" @endif>
                                        {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($popular->isNotEmpty())
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
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
