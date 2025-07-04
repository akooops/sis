<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Saud international schools - Admin panel</title>
        <!-- Favicon -->
        <link href="/assets/admin/images/logo.png" rel="shortcut icon"/>
        <!-- Metronic CSS -->
        <link href="/assets/admin/vendors/apexcharts/apexcharts.css" rel="stylesheet"/>
        <link href="/assets/admin/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
        <link href="/assets/admin/css/styles.css" rel="stylesheet"/>
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- Summernote CSS -->
        <link href="/assets/admin/vendors/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
        <!-- Flatpickr CSS -->
        <link href="/assets/admin/vendors/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
        <!-- FullCalendar CSS -->
        <link href="/assets/admin/vendors/fullcalendar/main.min.css" rel="stylesheet" type="text/css" />
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

          .select2{
            width: 100% !important;
          }

          .select2-selection--single{
              display: flex !important;
          }
          
          .select2-container .select2-selection--single .select2-selection__rendered{
              padding-left: 0 !important;
          }

          .select2-container .select2-selection--single .select2-selection__clear{
              cursor: pointer !important;
          }
          
          .note-editable{
            background-color: #fff
          } 

          .sortable-list {
            list-style: none;
            padding: 0;
            margin: 0;
          }
          .sortable-list .list-group-item {
              cursor: move;
              margin: 5px 0;
              background: #f8f9fa;
              border: 1px solid #dee2e6;
              padding: 15px;
              border-radius: 8px;
              position: relative;
              transition: all 0.2s ease;
          }
          .sortable-list .list-group-item:hover {
              background: #e9ecef;
              border-color: #adb5bd;
          }
          .handle {
              margin-right: 15px;
              cursor: move;
              color: #6c757d;
              font-size: 18px;
          }
          .sortable-ghost {
              opacity: 0.4;
          }
          .sortable-chosen {
              background-color: #e3f2fd !important;
              border-color: #2196f3 !important;
          }
          .sortable-drag {
              background-color: #fff3cd !important;
              border-color: #ffc107 !important;
              box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <!-- Select2 JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <!-- Summernote JavaScript -->
        <script src="/assets/admin/vendors/summernote/summernote-lite.min.js"></script>
        <!-- Flatpickr JavaScript -->
        <script src="/assets/admin/vendors/flatpickr/flatpickr.min.js"></script>   
        <!-- FullCalendar JavaScript -->
        <script src="/assets/admin/vendors/fullcalendar/main.min.js"></script>
        <!-- Sortable JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <!-- Metronic Layout Scripts -->
        <script src="/assets/admin/js/widgets/general.js"></script>
    </body>
</html>