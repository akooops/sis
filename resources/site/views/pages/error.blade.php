{{--
    The 404 page.

    Built like any other page — hero, breadcrumb, body — rather than as a bare
    centred message, because a 404 is still a page of this site and dropping the
    chrome makes it read as a server fault instead of a wrong address. The `error`
    Page row is seeded, so an admin can give it artwork and copy; with no
    thumbnail the hero falls back to the bundled placeholder, exactly as every
    other page does.

    THE ONE PAGE THAT BUILDS ITS OWN $seo, because it has no controller: it is
    rendered straight out of App\Exceptions\Handler, which knows only that
    something 404'd.

    NULL-SAFE THROUGHOUT, deliberately. This renders inside the exception
    handler, so anything that throws here turns a 404 into a 500 with no useful
    trace. The Page is optional: if it is missing, unpublished, or the database
    is unreachable, the page still shows a translated message and a way home.
    `alternates` is empty for the same reason and because a wrong address has no
    translations to offer — the drawer's switcher already falls back to each
    locale's home.

    DELIBERATELY NOT IN AN `errors/` DIRECTORY. Laravel's RegisterErrorViewPaths
    maps every config('view.paths') entry plus /errors into the `errors`
    namespace, so a file there would answer `errors::404` for the ADMIN and the
    API too, with no way to opt out. App\Exceptions\Handler renders this one by
    name, only for site requests.
--}}
@php
    /*
     * A try/catch, and not defensiveness for its own sake: a 404 raised because
     * the database is down would otherwise throw again on this very query.
     */
    try {
        $errorPage = \App\Models\Page::query()->live()->where('slug', 'error')->first();
    } catch (\Throwable) {
        $errorPage = null;
    }

    $title = $errorPage?->getTranslation('title', $site->locale(), true) ?: __('site.error.title');
    $body = $errorPage?->getTranslation('description', $site->locale(), true) ?: __('site.error.body');
    $content = $errorPage?->getTranslation('content', $site->locale(), true);

    $seo = [
        'title' => $title,
        'description' => $body,
        'image' => $errorPage?->thumbnail_url,
        'canonical' => url()->current(),
        'robots' => 'noindex,follow',
        'type' => 'website',
        'alternates' => [],
    ];

    $breadcrumbs = [];
@endphp

@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $errorPage?->thumbnail_url,
        'title' => $title,
    ])

    <section>
        <div class="container pb-14 pt-10 text-center">
            <p class="mb-2 text-9xl font-black leading-none text-brand-tint">404</p>

            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand">{{ $title }}</h2>

            <hr class="mx-auto mb-8 mt-2 max-w-xl border-line">

            <p class="mx-auto mb-8 max-w-xl">{{ $body }}</p>

            @if ($content)
                <div class="prose mx-auto mb-8 text-start">{!! $content !!}</div>
            @endif

            <a class="btn" href="{{ route('web.site.home') }}">
                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                @lang('common.back_home')
            </a>
        </div>
    </section>
@endsection
