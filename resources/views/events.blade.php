@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('events'))
@section('css')
<link href="{{ URL::asset('assets/libs/calendar/main.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $page->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$page->getLocalTranslation('title')}}
                </h1>

            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

@if($page->menu)
<section class="wrapper bg-light-primary">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($page->menu->items as $menuItem)
                @php
                    $menuUrl = $menuItem->page
                        ? route('page', ['slug' => $menuItem->page->slug])
                        : $menuItem->url;
                    $currentUrl = url()->current();
                @endphp

                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a class="text-uppercase" href="#">Home</a></li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$page->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper bg-light">
    <div class="container pt-6 pb-12">
        <div id="calendar"></div>
    </div>
</section>
@endsection
@section('script')
<script src="{{ URL::asset('assets/libs/calendar/main.min.js')}}"></script>
<script src='{{ URL::asset('assets/libs/calendar/locales-all.min.js')}}'></script>

<script>

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    locale: '{{app()->getLocale()}}',
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next',
      center: 'title',
      right: ''
    },
    events: [
      @foreach($events as $event)
      {
        title: '{{$event->getLocalTranslation("title")}}',
        start: '{{$event->starts_at}}',
        end: '{{$event->ends_at}}',
        url: '{{route("event", ["slug" => $event->slug])}}'
      },
      @endforeach
    ]
  });

  calendar.render();
});

</script>
@endsection