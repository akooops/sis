@extends('layouts.master')
@section('title', $album->getLocalTranslation('title'))
@section('description', $album->getLocalTranslation('description'))
@section('canonical', route('albums', ['slug' => $album->slug]))
@section('image', $album->thumbnailUrl)
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
                    <div class="banner-img" style="background-image: url('{{ $album->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$album->getLocalTranslation('title')}}
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

<section class="wrapper">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('albums')}}">
                        {{getLanguageKeyLocalTranslation('breadcrumbs_albums_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$album->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper">
    <div class="container pt-6 pb-12 page-content-section">
        <div class="row">
            <h2 data-aos="fade-up" data-aos-duration="1000">
                {{$album->getLocalTranslation('title')}}
            </h2>

            <p data-aos="fade-up" data-aos-duration="1500">
                {{$album->getLocalTranslation('description')}}
            </p>

            <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">
        </div>

        <div class="row mt-5 gx-md-6 gy-6" data-aos="fade-up" data-aos-duration="2000">
            @foreach ($album->files as $file)
                <div class="item col-12 col-sm-6 col-md-4">
                    <figure class="hover-scale rounded cursor-dark">
                        <a href="{{$file->url}}" data-glightbox="" data-gallery="project-1">
                            <img src="{{$file->url}}" alt="">
                        </a>
                    </figure>
                </div>
                <!--/column -->
            @endforeach
        </div>
    </div>
</section>
@endsection
@section('script')
@endsection