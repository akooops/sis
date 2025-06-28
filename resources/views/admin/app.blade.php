<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="follow, index" name="robots"/>
    <meta content="" name="description"/>
    <meta content="@keenthemes" name="twitter:site"/>
    <meta content="@keenthemes" name="twitter:creator"/>
    <meta content="summary_large_image" name="twitter:card"/>
    <meta content="SIS - School Information System" name="twitter:title"/>
    <meta content="" name="twitter:description"/>
    <meta content="assets/media/app/og-image.png" name="twitter:image"/>
    <meta content="en_US" property="og:locale"/>
    <meta content="website" property="og:type"/>
    <meta content="@keenthemes" property="og:site_name"/>
    <meta content="SIS - School Information System" property="og:title"/>
    <meta content="" property="og:description"/>
    <meta content="assets/media/app/og-image.png" property="og:image"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link href="/assets/admin/media/app/favicon.ico" rel="shortcut icon"/>
    <link href="/assets/admin/media/app/favicon-32x32.png" rel="icon" sizes="32x32" type="image/png"/>
    <link href="/assets/admin/media/app/favicon-16x16.png" rel="icon" sizes="16x16" type="image/png"/>
    <link href="/assets/admin/media/app/apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180"/>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Metronic CSS -->
    <link href="/assets/admin/vendors/apexcharts/apexcharts.css" rel="stylesheet"/>
    <link href="/assets/admin/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
    <link href="/assets/admin/css/styles.css" rel="stylesheet"/>

    @routes
    @vite('resources/js/app.js')
    @inertiaHead
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