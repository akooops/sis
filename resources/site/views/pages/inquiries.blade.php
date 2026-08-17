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

            @include('site::partials.ui.flash')

            @if ($page->getTranslation('content', $site->locale(), true))
                @include('site::partials.content.content-styles', ['model' => $page])

                {{-- id="page-content" is the scope hook the admin's own CSS
                     targets. See site::partials.content.content-styles. --}}
                <div id="page-content" class="prose mb-8" data-aos="fade-up" data-aos-duration="1000">
                    {!! $page->getTranslation('content', $site->locale(), true) !!}
                </div>
            @endif

            @include('site::partials.forms.embed')
        </div>
    </section>
@endsection
