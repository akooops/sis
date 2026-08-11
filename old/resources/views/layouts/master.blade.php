@php
    $currentLanguage = getCurrentLanguage();
    $isRtl = $currentLanguage && $currentLanguage->is_rtl;

    $googleAnalyticsIdSetting = getSetting('google_analytics_id');
    $headCodeSetting = getSetting('head_code');
    $footCodeSetting = getSetting('foot_code');
    $supportButtonCodeSetting = getSetting('support_button_code');
@endphp

<!DOCTYPE html>
<html lang="{{ $currentLanguage?->code ?? app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ getLanguageKeyLocalTranslation('website_title') }} - @yield('title')</title>
    <meta name="description" content="@yield('description')" />
    <link rel="canonical" href="@yield('canonical')" />

    <meta property="og:title" content="{{ getLanguageKeyLocalTranslation('website_title') }} - @yield('title')" />
    <meta property="og:description" content="@yield('description')" />
    <meta property="og:image" content="@hasSection('image')@yield('image')@else{{ URL::asset('assets/img/logo.png') }}@endif" />

    <meta name="twitter:title" content="{{ getLanguageKeyLocalTranslation('website_title') }} - @yield('title')" />
    <meta name="twitter:description" content="@yield('description')" />
    <meta name="twitter:image" content="@hasSection('image')@yield('image')@else{{ URL::asset('assets/img/logo.png') }}@endif" />

    <link rel="shortcut icon" href="{{ URL::asset('assets/img/favicon.png') }}" />

    {{-- Fonts are otherwise only discovered once the stylesheet has parsed, which
         costs a round trip. Preload the two text weights used above the fold plus
         the icon font, in the current language's family only. --}}
    @php
        $fontDir = $isRtl ? 'DINNext' : 'Mulish';
        $fontFaces = $isRtl
            ? ['DINNextLTArabic-Regular', 'DINNextLTArabic-Bold']
            : ['Mulish-Regular', 'Mulish-Bold'];
    @endphp
    @foreach ($fontFaces as $fontFace)
        <link rel="preload" as="font" type="font/woff2" crossorigin
            href="{{ URL::asset("assets/css/fonts/{$fontDir}/{$fontFace}.woff2") }}" />
    @endforeach
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ URL::asset('assets/fonts/unicons/Unicons.woff2') }}" />

    @vite('resources/js/site.js')

    @yield('css')

    @if ($googleAnalyticsIdSetting && !empty($googleAnalyticsIdSetting->value))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsIdSetting->value }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ $googleAnalyticsIdSetting->value }}');
        </script>
    @endif

    @if ($headCodeSetting && !empty($headCodeSetting->value))
        {!! $headCodeSetting->value !!}
    @endif
</head>

<body class="bg-paper text-body antialiased">
    @include('layouts.header')

    <main>
        @yield('content')
    </main>

    @include('layouts.footer')

    {{-- Scroll-to-top ring. Shifts inboard when a support widget occupies the corner. --}}
    <button type="button"
        class="scroll-top {{ $supportButtonCodeSetting && !empty($supportButtonCodeSetting->value) ? 'bottom-[90px]' : '' }}"
        aria-label="{{ getLanguageKeyLocalTranslation('website_title') }}">
        <svg class="w-full h-full" viewBox="-1 -1 102 102" aria-hidden="true">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </button>

    @if ($supportButtonCodeSetting && !empty($supportButtonCodeSetting->value))
        {!! $supportButtonCodeSetting->value !!}
    @endif

    @if ($footCodeSetting && !empty($footCodeSetting->value))
        {!! $footCodeSetting->value !!}
    @endif

    @yield('script')
    @yield('script-bottom')
</body>

</html>
