@php
    $currentLanguage = getCurrentLanguage();
@endphp

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="utf-8" />
    <title>{{getLanguageKeyLocalTranslation('website_title')}} - @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description')">

    <meta property="og:title" content="{{getLanguageKeyLocalTranslation('website_title')}} - @yield('title')" />
    <meta property="og:description" content="@yield('description')" />
    <meta property="og:image" content="@hasSection('image')@yield('image')@else{{ URL::asset('assets/img/logo.png')}}@endif" />

    <meta name="twitter:title" content="{{getLanguageKeyLocalTranslation('website_title')}} - @yield('title')" />
    <meta name="twitter:description" content="@yield('description')" />
    <meta property="twitter:image" content="@hasSection('image')@yield('image')@else{{ URL::asset('assets/img/logo.png')}}@endif" />

    <link rel="canonical" href="@yield('canonical')" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('assets/img/favicon.png')}}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
    
    @include('layouts.head-css')
</head>

<body dir="{{($currentLanguage && $currentLanguage->is_rtl) ? 'rtl' : 'ltr'}}">
    <div class="content">
        <!-- Begin page -->
        <div class="content-wrapper">
            @include('layouts.header')

            @yield('content')
        </div>
        <!-- END layout-wrapper -->
        @include('layouts.footer')

        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
</body>

</html>
