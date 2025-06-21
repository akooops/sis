@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('albums'))
@section('css')
@endsection
@section('content')
<section class="wrapper banners-section">
    <div class="swiper-container" 
        data-margin="0" 
        data-autoplay="true" 
        data-autoplaytime="7000" 
        data-nav="true" 
        data-dots="true" 
        data-items="1">
        
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $page->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$page->getLocalTranslation('title')}}
                                    </h1>
                                </div>
                                <!--/.col -->
                            </div>     
                            <!--/.row -->      
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.container -->
                </div>
                <!--/.swiper-slide -->            
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->


@if($page->menu)
<section class="wrapper page-menu-section">
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
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('index')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_index_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$page->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper">
    <div class="container pt-6 pb-12 albums-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="row">
            @foreach ($albums as $album)   
                <div class="col-12 col-sm-6 col-md-4 pb-4">
                    <div class="card shadow-lg">
                        <figure class="hover-scale">
                            <a href="{{route('album', ['slug' => $album->slug])}}"> 
                                <img src="{{$album->thumbnailUrl}}" alt="" />
                            </a>
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