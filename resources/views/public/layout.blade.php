{{--
    TEMPORARY PUBLIC LAYOUT.

    This is a placeholder for the real end-user design, and is deliberately
    plain: no theme, no navigation, no branding beyond the app name. When the
    public site is designed, replace this file and the views that extend it.

    What is NOT temporary and must survive that redesign:
      - the @vite entries below (public.css / public.js, never app.*, which
        would drag the whole Metronic admin theme onto a public page);
      - the <div data-sisf-root> mount point;
      - the JSON payload blocks, which are <script type="application/json">
        rather than a JS variable because the schema carries admin-authored HTML
        and a stray </script> inside a string literal would break out of it.
--}}
<!doctype html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($direction ?? null) ?: 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <title>@yield('title', config('app.name'))</title>

    @vite(['resources/css/public.css', 'resources/js/public.js'])

    @stack('head')
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto w-full max-w-2xl px-4 py-10 sm:py-16">
        @yield('content')
    </main>

    <footer class="mx-auto w-full max-w-2xl px-4 pb-10 text-xs text-zinc-500">
        &copy; {{ now()->year }} {{ config('app.name') }}
    </footer>
</body>
</html>
