@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.menu', ['menu' => $page->menu])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                {{ $page->getTranslation('title', $site->locale(), true) ?: $page->name }}
            </h2>

            <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @if ($page->getTranslation('content', $site->locale(), true))
                @include('site::partials.content.content-styles', ['model' => $page])

                {{-- id="page-content" is the scope hook the admin's own CSS
                     targets. See site::partials.content.content-styles. --}}
                <div id="page-content" class="prose mb-8" data-aos="fade-up" data-aos-duration="1000">
                    {!! $page->getTranslation('content', $site->locale(), true) !!}
                </div>
            @endif

            @if ($facilities->isEmpty())
                <div class="alert alert-warning flex items-center gap-2" role="alert" data-aos="fade-up"
                    data-aos-duration="1000">
                    <i class="uil uil-info-circle" aria-hidden="true"></i>
                    <span class="flex-1">@lang('facilities.empty')</span>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
                    @foreach ($facilities as $facility)
                        @php($facilityTitle = $facility->getTranslation('title', $site->locale(), true) ?: $facility->name)
                        @php($url = route('web.site.facilities.show', ['slug' => $facility->slug]))

                        <article class="card media-card" data-aos="fade-up" data-aos-duration="1000">
                            <figure class="overlay media-figure h-[350px]">
                                <a href="{{ $url }}">
                                    <img class="h-full w-full object-cover"
                                        src="{{ $facility->thumbnail_url ?: asset('assets/site/images/placeholder.png') }}"
                                        alt="{{ $facilityTitle }}" loading="lazy">
                                </a>
                            </figure>

                            <div class="card-body">
                                <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                    <a class="hover:text-brand-soft" href="{{ $url }}">{{ $facilityTitle }}</a>
                                </h2>

                                <p class="mb-0 line-clamp-3">
                                    {{ $facility->getTranslation('description', $site->locale(), true) }}
                                </p>
                            </div>

                            <div class="card-footer">
                                <a class="btn btn-sm" href="{{ $url }}">@lang('facilities.view')</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
