@extends('layouts.master')
@section('title', $event->getLocalTranslation('title'))
@section('description', $event->getLocalTranslation('description'))
@section('canonical', route('event', ['slug' => $event->slug]))
@section('image', $event->thumbnailUrl)
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $event->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$event->getLocalTranslation('title')}}
                </h1>

            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

@if($event->menu)
<section class="wrapper bg-light-primary">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($event->menu->items as $menuItem)
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
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('events')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_events_page_title')}}
                </a>
            </li>

            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$event->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper bg-light">
   <div class="container py-12 py-md-5">
    <h2 class="display-5 mb-3">
        {{$event->getLocalTranslation('title')}}
    </h2>

    <p class="lead fs-md">
        {{$event->getLocalTranslation('description')}}
    </p>

    <hr class="mt-2 mb-4">

    <div class="w-100 page-content">
        <x-markdown>
            {{ $event->getLocalTranslation('content') }}
        </x-markdown>
    </div>

   </div>
   <!-- /.container -->
</section>

@endsection
@section('script')
@endsection