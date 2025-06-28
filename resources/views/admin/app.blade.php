<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Saud international schools - Admin panel</title>
        <!-- Favicon -->
        <link href="/assets/admin/images/logo.png" rel="shortcut icon"/>
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
        <!-- Metronic CSS -->
        <link href="/assets/admin/vendors/apexcharts/apexcharts.css" rel="stylesheet"/>
        <link href="/assets/admin/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
        <link href="/assets/admin/css/styles.css" rel="stylesheet"/>
        @routes
        @vite('resources/js/app.js')
        @inertiaHead

        <style>
          #app{
              width: 100%;
              min-height: 100vh;
          }
          .kt-skeleton {
              background: linear-gradient(90deg, var(--accent) 25%, var(--border) 50%, var(--accent) 75%);
              background-size: 200% 100%;
              animation: shimmer 1.5s infinite;
              border-radius: 4px;
          }
          
          @keyframes shimmer {
              0% { background-position: -200% 0; }
              100% { background-position: 200% 0; }
          }
          
          .w-fit{
            width: fit-content;
          }
      </style>

    </head>
    <body class="antialiased flex h-full text-base text-foreground bg-background [--header-height:60px] [--sidebar-width:270px] bg-mono dark:bg-background">
      <!-- Theme Mode -->
        <script>
            const defaultThemeMode = 'light';
               let themeMode;
            
               if (document.documentElement) {
                 if (localStorage.getItem('kt-theme')) {
                   themeMode = localStorage.getItem('kt-theme');
                 } else if (
                   document.documentElement.hasAttribute('data-kt-theme-mode')
                 ) {
                   themeMode =
                     document.documentElement.getAttribute('data-kt-theme-mode');
                 } else {
                   themeMode = defaultThemeMode;
                 }
            
                 if (themeMode === 'system') {
                   themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches
                     ? 'dark'
                     : 'light';
                 }
            
                 document.documentElement.classList.add(themeMode);
               }
        </script>
        <!-- End of Theme Mode -->
        @inertia
        <!-- Metronic JavaScript -->
        <script src="/assets/admin/js/core.bundle.js"></script>
        <script src="/assets/admin/vendors/apexcharts/apexcharts.min.js"></script>
        <script src="/assets/admin/vendors/ktui/ktui.min.js"></script>
        <script src="/assets/admin/vendors/clipboard/clipboard.min.js"></script>
        <script src="/assets/admin/vendors/prismjs/prismjs.min.js"></script>
        <script src="/assets/admin/vendors/@form-validation/form-validation.bundle.js"></script>
        <!-- Metronic Layout Scripts -->
        <script src="/assets/admin/js/widgets/general.js"></script>
    </body>
</html>