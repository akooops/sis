<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Saud International Schools</title>

        <link href="{{ asset('assets/media/app/favicon.png') }}" rel="shortcut icon" />

        <!-- Theme mode bootstrap (applies stored light/dark before paint) -->
        <script>
            (function () {
                const stored = localStorage.getItem('kt-theme');
                let mode = stored || document.documentElement.getAttribute('data-kt-theme-mode') || 'light';
                if (mode === 'system') {
                    mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.classList.add(mode);
            })();
        </script>

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
        @inertia
    </body>
</html>
