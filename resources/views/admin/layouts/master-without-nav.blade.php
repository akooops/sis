<!doctype html>
<html data-topbar="light" data-sidebar-image="none">

    <head>
    <meta charset="utf-8" />
    <title>@yield('title') | Saud international schools - Admin Control Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('assets/img/favicon.png')}}">
        @include('admin.layouts.head-css')
  </head>

    @yield('body')

    @yield('content')

    @include('admin.layouts.vendor-scripts')
    </body>
</html>
