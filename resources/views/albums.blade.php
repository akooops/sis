@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('albums'))
@section('css')
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
        <div class="row">
            @foreach ($albums as $album)   
                <div class="col-12 col-sm-6 col-md-4 pb-4">
                    <div class="card shadow-lg">
                        <figure class="album-figure card-img-top overlay overlay-1">
                            <a href="{{route('album', ['slug' => $album->slug])}}"> 
                                <img src="{{$album->thumbnailUrl}}" alt="" />
                            </a>
                            <figcaption>
                                <h5 class="from-top mb-0">Read More</h5>
                            </figcaption>
                        </figure>
                    </div>
                    <!-- /.card -->
                </div>
            @endforeach
        </div>
        <!-- /.row -->

        <nav class="d-flex" aria-label="pagination">
            <ul class="pagination">
                <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
                    <a class="page-link" href="{{ route('albums', array_merge(['page' => $pagination["prevPage"]], request()->only('search'))) }}" aria-label="Previous">
                    <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
                    </a>
                </li>

                @foreach($pagination["pages"] as $page)
                    <li class="page-item">
                        <a class="page-link {{($page == $pagination['currentPage']) ? 'active' : ''}}" href="{{ route('albums', array_merge(['page' => $page], request()->only('search'))) }}">
                            {{$page}}
                        </a>
                    </li>
                @endforeach

                <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                    <a class="page-link" href="{{ route('albums', array_merge(['page' => $pagination["nextPage"]], request()->only('search'))) }}" aria-label="Next">
                    <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
                    </a>
                </li>
            </ul>
            <!-- /.pagination -->
        </nav>
        <!-- /nav -->
    </div>
</section>
@endsection
@section('script')
@endsection