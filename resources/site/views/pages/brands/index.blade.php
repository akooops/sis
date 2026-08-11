@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    @include('site::partials.page-body', [
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
        'subtitle' => $page->getTranslation('description', $site->locale(), true),
        'content' => $page->getTranslation('content', $site->locale(), true),
    ])

    <section>
        <div class="container pb-14">
            <div class="grid auto-rows-fr gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
                @foreach ($brands as $brand)
                    <article class="card media-card" data-aos="fade-up" data-aos-duration="1000">
                        <figure class="overlay h-[300px]">
                            <a href="{{ route('web.site.brands.show', ['slug' => $brand->slug]) }}">
                                <img class="h-full w-full object-cover" src="{{ $brand->thumbnail_url }}" alt="{{ $brand->getTranslation('title', $site->locale(), true) ?: $brand->name }}" />
                            </a>
                        </figure>
            
                        <div class="card-body p-4">
                            <h3 class="mb-2">{{ $brand->getTranslation('title', $site->locale(), true) ?: $brand->name }}</h3>
            
                            <p>{{ $brand->getTranslation('description', $site->locale(), true) }}</p>
            
                            <a href="{{ route('web.site.brands.show', ['slug' => $brand->slug]) }}" class="btn btn-sm mt-2 self-start">
                                @lang('site.identity.view')
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
