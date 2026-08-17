<!doctype html>
<html lang="{{ $site->locale() }}" dir="{{ $site->direction() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO --}}
    <title>{{ $seo['title'] }} - @lang('common.site_title')</title>

    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    
    <meta name="description" content="{{ $seo['description'] }}">

    @foreach ($seo['alternates'] as $code => $url)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
    @endforeach

    {{-- ?-> and not ->: defaultLanguage() is null when no enabled language is
         flagged default, and this same layout renders the 404 from inside the
         exception handler — a fatal here would turn that 404 into a 500. --}}
    <link rel="alternate" hreflang="x-default"
        href="{{ $seo['alternates'][$site->defaultLanguage()?->code] ?? $seo['canonical'] }}">
    
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:site_name" content="@lang('common.site_title')">
    <meta property="og:title" content="{{ $seo['title'] }} - @lang('common.site_title')">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:locale" content="{{ $site->locale() }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">

    @foreach ($seo['alternates'] as $code => $url)
        <meta property="og:locale:alternate" content="{{ $code }}">
    @endforeach

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }} - @lang('common.site_title')">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['image'] }}">

    {{-- Fonts --}}
    @php
        $family = $site->isRtl() ? 'DINNext' : 'Mulish';

        $faces = $site->isRtl()
            ? ['DINNextLTArabic-Regular', 'DINNextLTArabic-Bold']
            : ['Mulish-Regular', 'Mulish-Bold'];
    @endphp

    @foreach ($faces as $face)
        <link rel="preload" as="font" type="font/woff2" crossorigin
            href="{{ asset("assets/site/fonts/{$family}/{$face}.woff2") }}">
    @endforeach

    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('assets/site/fonts/unicons/Unicons.woff2') }}">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('assets/site/images/favicon.png') }}">

    {{-- Styles and scripts bundle --}}
    @vite(['resources/site/css/site.css', 'resources/site/js/site.js'], 'build/site')

    {{-- Analytics integration --}}
    @if ($site->analytics()->enabled())
        @include('site::partials.layout.analytics')
    @endif

    {{-- Head code --}}
    @if ($head = $site->setting('code.head'))
        {!! $head !!}
    @endif

    @stack('head')
</head>

<body class="bg-paper text-body antialiased">
    <a class="sr-only focus:not-sr-only" href="#content">@lang('nav.skip_to_content')</a>

    @include('site::partials.layout.header')

    <main id="content">
        @yield('content')
    </main>

    @include('site::partials.layout.footer')

    {{-- Scroll to top --}}
    <button type="button" class="scroll-top" aria-label="@lang('common.scroll_top')">
        <svg class="h-full w-full" viewBox="-1 -1 102 102" aria-hidden="true">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </button>

    {{-- Foot code --}}
    @if ($foot = $site->setting('code.foot'))
        {!! $foot !!}
    @endif

    @stack('scripts')
</body>

</html>
