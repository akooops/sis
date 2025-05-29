@php
    $currentLanguage = getCurrentLanguage();
@endphp

@yield('css')

@if($currentLanguage && $currentLanguage->is_rtl)
<!-- Plugins Css -->
<link href="{{ URL::asset('assets/css/plugins.rtl.css') }}" rel="stylesheet" type="text/css" />
<!-- Theme Css -->
<link href="{{ URL::asset('assets/css/theme.rtl.css') }}" rel="stylesheet" type="text/css" />
<!-- custom Css-->
<link href="{{ URL::asset('assets/css/main.rtl.css') }}" rel="stylesheet" type="text/css" />
{{-- @yield('css') --}}  
@else
<!-- Plugins Css -->
<link href="{{ URL::asset('assets/css/plugins.css') }}" rel="stylesheet" type="text/css" />
<!-- Theme Css -->
<link href="{{ URL::asset('assets/css/theme.css') }}" rel="stylesheet" type="text/css" />
<!-- custom Css-->
<link href="{{ URL::asset('assets/css/main.css') }}" rel="stylesheet" type="text/css" />
{{-- @yield('css') --}}  
@endif
