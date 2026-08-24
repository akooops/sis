@extends('site::layout')

@section('content')
    @php($facilityTitle = $facility->getTranslation('title', $site->locale(), true) ?: $facility->name)

    @include('site::partials.content.hero', [
        'image' => $facility->thumbnail_url,
        'title' => $facilityTitle,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            @include('site::partials.content.body', [
                'title' => $facilityTitle,
                'subtitle' => $facility->getTranslation('description', $site->locale(), true),
                'content' => $facility->getTranslation('content', $site->locale(), true),
                // Carries css_url + custom_css, so an admin can style this venue's
                // own body without touching the rest of the site.
                'styles' => $facility,
            ])

            {{-- The two things you can do from here. Each is its own URL rather
                 than a form on this page: the renderer emits one payload per page,
                 and a visitor arrives wanting one of the two anyway. --}}
            <div class="mt-8 flex flex-wrap gap-3" data-aos="fade-up" data-aos-duration="1000">
                <a class="btn" href="{{ route('web.site.facilities.reserve', ['slug' => $facility->slug]) }}">
                    <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                    @lang('facilities.cta.book')
                </a>
                <a class="btn btn-outline" href="{{ route('web.site.facilities.contact', ['slug' => $facility->slug]) }}">
                    <i class="uil uil-envelope" aria-hidden="true"></i>
                    @lang('facilities.cta.contact')
                </a>
            </div>

            {{-- ATTACHED, NOT OWNED. Each of these keeps its own URL and its own
                 listing; the venue merely says which ones are about it. The heading
                 renders only with rows under it — an empty "News" on a venue page
                 would claim the venue has news somewhere else. --}}
            @if ($facility->articles->isNotEmpty())
                <div class="mt-12" data-aos="fade-up" data-aos-duration="1000">
                    <h3 class="mb-4 text-3xl font-black uppercase text-brand">@lang('facilities.sections.news')</h3>
                    <hr class="mb-6 mt-2 border-line">

                    <div class="grid auto-rows-fr gap-4 md:grid-cols-2">
                        @foreach ($facility->articles as $article)
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
                </div>
            @endif

            @if ($facility->albums->isNotEmpty())
                <div class="mt-12" data-aos="fade-up" data-aos-duration="1000">
                    <h3 class="mb-4 text-3xl font-black uppercase text-brand">@lang('facilities.sections.albums')</h3>
                    <hr class="mb-6 mt-2 border-line">

                    {{-- Image-only, like /albums: an album's cover IS its title. --}}
                    <div class="grid auto-rows-fr gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
                        @foreach ($facility->albums as $album)
                            <article class="card media-card">
                                <figure class="overlay h-[450px]">
                                    <a href="{{ route('web.site.albums.show', ['slug' => $album->slug]) }}">
                                        <img class="h-full w-full object-cover" src="{{ $album->thumbnail_url }}"
                                            alt="{{ $album->getTranslation('title', $site->locale(), true) }}"
                                            loading="lazy">
                                    </a>
                                </figure>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
