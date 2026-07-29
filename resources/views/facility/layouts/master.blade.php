@php
    $currentLanguage = getCurrentLanguage();

    $themeColor = $facility->themeColor;
    $themeSecondaryColor = $facility->themeSecondaryColor;

    $r = $g = $b = 0;
    sscanf($themeColor, "#%02x%02x%02x", $r, $g, $b);
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8" />
    <title>{{ transOrDefault($facility, 'title') }} - @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="@yield('description')">

    <meta property="og:title" content="{{ transOrDefault($facility, 'title') }} - @yield('title')" />
    <meta property="og:description" content="@yield('description')" />
    <meta property="og:image" content="@hasSection('image')@yield('image')@else{{ $facility->thumbnailUrl }}@endif" />

    <link rel="canonical" href="@yield('canonical')" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $facility->logoUrl ?? URL::asset('assets/img/favicon.png') }}">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    @include('layouts.head-css')

    <!-- Facility theme -->
    <style>
        :root {
            --bs-primary: {{ $themeColor }};
            --bs-primary-rgb: {{ $r }}, {{ $g }}, {{ $b }};
            --facility-primary: {{ $themeColor }};
            --facility-secondary: {{ $themeSecondaryColor }};
        }

        .btn-primary {
            background-color: {{ $themeColor }} !important;
            border-color: {{ $themeColor }} !important;
        }

        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: {{ $themeSecondaryColor }} !important;
            border-color: {{ $themeSecondaryColor }} !important;
        }

        .text-primary, a.text-primary:hover {
            color: {{ $themeColor }} !important;
        }

        .bg-primary, .navbar-cta-container {
            background-color: {{ $themeColor }} !important;
        }

        .breadcrumb-item a, .nav-link.active, .post-meta a {
            color: {{ $themeColor }};
        }

        /* Facility navbar: always solid white, incl. the sticky clone the theme
           creates on scroll, so links never sit on a transparent background. */
        .facility-navbar,
        .facility-navbar.navbar-clone,
        .facility-navbar.navbar-stick,
        .facility-navbar.fixed {
            background-color: #fff !important;
            box-shadow: 0 0 1.25rem rgba(30, 34, 40, 0.06);
        }

        .facility-navbar .nav-link,
        .facility-navbar .navbar-brand span {
            color: #343f52;
        }

        /* Dark footer: keep every text node light regardless of theme defaults. */
        footer.facility-footer,
        footer.facility-footer p,
        footer.facility-footer span,
        footer.facility-footer address,
        footer.facility-footer li {
            color: rgba(255, 255, 255, 0.75);
        }

        footer.facility-footer .widget-title,
        footer.facility-footer h4 {
            color: #fff;
        }

        footer.facility-footer a,
        footer.facility-footer .link-body {
            color: rgba(255, 255, 255, 0.85);
        }

        footer.facility-footer a:hover {
            color: #fff;
        }
    </style>
</head>

<body dir="{{ ($currentLanguage && $currentLanguage->is_rtl) ? 'rtl' : 'ltr' }}">
    <div class="content">
        <!-- Begin page -->
        <div class="content-wrapper">
            @include('facility.partials.header')

            @yield('content')
        </div>
        <!-- END layout-wrapper -->
        @include('facility.partials.footer')

        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    @include('layouts.vendor-scripts')
</body>

</html>
