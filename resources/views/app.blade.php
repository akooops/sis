<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Novonordisk supply chain management system </title>

        <!-- Favicon -->
        <link href="{{asset('assets/media/app/mini-logo.svg')}}" rel="shortcut icon"/>

        <!-- Metronic CSS -->
        <link href="{{asset('assets/vendors/apexcharts/apexcharts.css')}}" rel="stylesheet"/>
        <link href="{{asset('assets/vendors/keenicons/styles.bundle.css')}}" rel="stylesheet"/>
        <link href="{{asset('assets/css/theme.css')}}" rel="stylesheet"/>
        <link href="{{asset('assets/css/main.css')}}" rel="stylesheet"/>

        <!-- Font Awesome CSS -->
        <link href="{{asset('assets/vendors/fontawesome/css/fontawesome.css')}}" rel="stylesheet" />
        <link href="{{asset('assets/vendors/fontawesome/css/solid.css')}}" rel="stylesheet" />

        <!-- Select2 CSS -->
        <link href="{{asset('assets/vendors/select2/css/select2.min.css')}}" rel="stylesheet" />
        <!-- Flatpickr CSS -->
        <link href="{{asset('assets/vendors/flatpickr/css/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
        <!-- FullCalendar CSS -->
        <link href="{{asset('assets/vendors/fullcalendar/main.min.css')}}" rel="stylesheet" type="text/css" />
        <!-- Summernote CSS -->
        <link href="{{asset('assets/vendors/summernote/summernote-lite.min.css')}}" rel="stylesheet" type="text/css" />

        <!-- Intl Tel Input CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>

        @routes
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
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
        <script src="{{asset('assets/js/core.bundle.js')}}"></script>
        <!-- Metronic Layout Scripts -->
        <script src="{{asset('assets/js/widgets/general.js')}}"></script>

        <!-- ApexCharts -->
        <script src="{{asset('assets/vendors/apexcharts/apexcharts.min.js')}}"></script>
        <!-- KTUI -->
        <script src="{{asset('assets/vendors/ktui/ktui.min.js')}}"></script>
        <!-- Clipboard -->
        <script src="{{asset('assets/vendors/clipboard/clipboard.min.js')}}"></script>
        <!-- jQuery -->
        <script src="{{asset('assets/vendors/jquery/jquery.min.js')}}"></script>
        <!-- Select2 JavaScript -->
        <script src="{{asset('assets/vendors/select2/js/select2.min.js')}}"></script>
        <!-- Flatpickr JavaScript -->
        <script src="{{asset('assets/vendors/flatpickr/js/flatpickr.min.js')}}"></script>   
        <!-- FullCalendar JavaScript -->
        <script src="{{asset('assets/vendors/fullcalendar/main.min.js')}}"></script>
        <!-- Summernote JavaScript -->
        <script src="{{asset('assets/vendors/summernote/summernote-lite.min.js')}}"></script>
        <!-- QRCODE -->
        <script src="{{asset('assets/vendors/qrcode/qrcode.min.js')}}"></script>
        <!-- Barcode -->
        <script src="{{asset('assets/vendors/barcode/barcode.min.js')}}"></script>
        <!-- Intl Tel Input JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script>
    </body>
</html>