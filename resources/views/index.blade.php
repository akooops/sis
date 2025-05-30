@php
    $firstSectionCtaPageSetting = getSetting('index_page_first_section_cta_page');
    $firstSectionCtaPage = null;

    if($firstSectionCtaPageSetting) $firstSectionCtaPage = getPage($firstSectionCtaPageSetting->value);

    $secondSectionCtaPageSetting = getSetting('index_page_second_section_cta_page');
    $secondSectionCtaPage = null;

    if($secondSectionCtaPageSetting) $secondSectionCtaPage = getPage($secondSectionCtaPageSetting->value);

    $thirdSectionCtaPageSetting = getSetting('index_page_third_section_cta_page');
    $thirdSectionCtaPage = null;

    if($thirdSectionCtaPageSetting) $thirdSectionCtaPage = getPage($thirdSectionCtaPageSetting->value);
@endphp

@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('index'))
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark">
    <div class="swiper-container swiper-hero dots-over swiper-container-0" data-margin="0" data-autoplay="true" data-autoplaytime="7000" data-nav="false" data-dots="true" data-items="1">
        <div class="swiper swiper-initialized swiper-horizontal swiper-backface-hidden">
            <div class="swiper-wrapper" id="swiper-wrapper-f9c21ed344bd05ba" aria-live="off">

                @foreach ($banners as $banner)
                    <div class="swiper-slide bg-overlay bg-overlay-400 bg-dark bg-image swiper-slide-prev" data-image-src="{{ $banner->thumbnailUrl  }}" role="group" >
                        <div class="container h-100">
                            <div class="row h-100">
                                <div class="banner-container col-lg-6 text-center text-lg-start justify-content-center align-self-center align-items-start">
                                    <h2 class="display-1 fs-36 mt-12 fw-semibold mb-4 text-white animate__animated animate__slideInDown animate__delay-1s">
                                        {{$banner->getLocalTranslation('title')}}
                                    </h2>
                                    <p class="lead fs-16 fw-semibold lh-sm mb-7 text-white animate__animated animate__slideInRight animate__delay-2s">                   
                                        {{$banner->getLocalTranslation('subtitle')}}                                         
                                    </p>
                                    <div class="animate__animated animate__slideInUp animate__delay-3s">
                                    <a href="{{$banner->page ? route('page', ['slug' => $banner->page->slug]) : $banner->url}}" 
                                        class="btn btn-sm bg-primary rounded text-white">
                                        {{$banner->getLocalTranslation('cta')}}
                                    </a>
                                    </div>
                                </div>
                                <!--/column -->

                                @if($banner->video)
                                    <div class="col-lg-6 pe-lg-8" data-cues="slideInDown" data-disabled="true">
                                        <div class="d-flex w-100 h-100 justify-content-center justify-content-lg-end align-items-start align-items-lg-center" data-cue="slideInDown">
                                            <a href="{{$banner->videoUrl}}" class="btn btn-circle btn-primary btn-play ripple" data-glightbox><i class="icn-caret-right"></i></a>
                                        </div>
                                        <!-- /div -->
                                    </div>
                                @endif
                            </div>
                            <!--/.row -->
                        </div>
                        <!--/.container -->
                    </div>
                    <!--/.swiper-slide -->
                @endforeach
            
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>

<section class="wrapper bg-light grades-section">
        <div class="row row-cols-1 row-cols-md-5 align-items-center">
            @foreach ($programs as $program)    
            <div class="col grades p-0">
                <figure class="overlay caption caption-overlay mb-0">
                    <a href="{{route('program', ['slug' => $program->slug])}}"> 
                      <img src="{{$program->thumbnailUrl}}" style="object-fit: cover" alt="">
                      <span class="bg"></span>
                    </a>

                    <figcaption>

                        <h2 class="post-title h3 mb-2 fw-normal">
                          <a href="{{route('program', ['slug' => $program->slug])}}">
                            {{$program->getLocalTranslation('title')}}
                          </a>
                        </h2>
                       
                        <span class="badge badge-lg bg-primary my-2 fw-semibold" style="color: white !important">
                            {{$program->getLocalTranslation('subtitle')}}
                        </span>
                    </figcaption>
                    <!-- /figcaption -->
                </figure>
            </div>
            @endforeach
        </div>
</section>

<section class="wrapper bg-light facts-section">
    <div class="container py-14">
        <div class="row gx-lg-0 gy-10 align-items-center">
            <div class="col-lg-6 order-lg-2 offset-lg-1 grid">
                <div class="row gx-md-5 gy-5 align-items-center counter-wrapper isotope">
                    <div class="item col-md-6">
                        <div class="card shadow-lg bg-primary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-soft-purple pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-presentation-check"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1 text-gold">
                                            {{getLanguageKeyLocalTranslation('index_page_first_number_value')}}
                                        </h3>
                                        <p class="mb-0 text-light">
                                            {{getLanguageKeyLocalTranslation('index_page_first_number_title')}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card shadow-lg bg-primary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-soft-red pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-users-alt"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1 text-gold">
                                            {{getLanguageKeyLocalTranslation('index_page_second_number_value')}}
                                        </h3>
                                        <p class="mb-0 text-light">
                                            {{getLanguageKeyLocalTranslation('index_page_second_number_title')}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card shadow-lg bg-primary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-soft-yellow pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-user-check"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1 text-gold">
                                            {{getLanguageKeyLocalTranslation('index_page_third_number_value')}}
                                        </h3>
                                        <p class="mb-0 text-light">
                                            {{getLanguageKeyLocalTranslation('index_page_third_number_title')}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card shadow-lg bg-primary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-soft-aqua pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-trophy"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1 text-gold">
                                            {{getLanguageKeyLocalTranslation('index_page_forth_number_value')}}
                                        </h3>
                                        <p class="mb-0 text-light">
                                            {{getLanguageKeyLocalTranslation('index_page_forth_number_title')}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                </div>
                <!--/.row -->
            </div>
            <!--/column -->
            <div class="col-lg-5">
                 <h3 class="display-5 mb-2 text-gold">
                    {{getLanguageKeyLocalTranslation('index_page_second_section_title')}}
                </h3>
                <p class="mb-4 text-light">
                   {{getLanguageKeyLocalTranslation('index_page_second_section_subtitle')}}
                </p>

                @if($secondSectionCtaPage)
                    <a href="{{route('page', ['slug' => $secondSectionCtaPage->slug])}}" class="btn btn-sm btn-primary rounded">
                        {{getLanguageKeyLocalTranslation('index_page_second_section_cta')}}
                    </a>
                @endif
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->


<section class="wrapper bg-light">
    <div class="container py-12">
        <div class="row align-items-center">
            <div class="col-lg-6 position-relative">
                <div class="shape shape-welcome bg-primary rellax d-md-block" data-rellax-speed="0"></div>
                
                <figure>
                  <img class="shape-welcome-image ms-6" src="{{ URL::asset('assets/img/home-1-welcome.png')}}" alt="">
                </figure>
            </div>
            <!--/column -->

            <div class="col-lg-6 mt-12 mt-lg-0 text-center text-lg-start">
                <h3 class="display-4 mb-2 text-gold">
                    {{getLanguageKeyLocalTranslation('index_page_third_section_title')}}
                </h3>
                
                <p class="mb-4">
                    {{getLanguageKeyLocalTranslation('index_page_third_section_subtitle')}}
                </p>

                @if($thirdSectionCtaPage)
                    <a href="{{route('page', ['slug' => $thirdSectionCtaPage->slug])}}" class="btn btn-sm btn-primary rounded">
                        <i class="uil uil-angle-right"></i>
                        {{getLanguageKeyLocalTranslation('index_page_third_section_cta')}}
                    </a>
                @endif
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper bg-light-primary">
    <div class="container py-12">
        <h3 class="display-5 mb-4 text-center text-gold">
            {{getLanguageKeyLocalTranslation('index_page_forth_section_title')}}
            <br>
            {{getLanguageKeyLocalTranslation('index_page_forth_section_subtitle')}}
        </h3>

        <div class="swiper-container blog grid-view mb-6" data-margin="30" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach ($articles as $article)   
                    <div class="swiper-slide">
                        <article>
                            <figure class="article-figure overlay overlay-1 hover-scale rounded mb-5">
                                <a href="{{route('article', ['slug' => $article->slug])}}">
                                    <img src="{{$article->thumbnailUrl}}" alt="" />
                                </a>
                                <figcaption>
                                    <h5 class="from-top mb-0"></h5>
                                </figcaption>
                            </figure>
                            <div class="post-header">
                                <h2 class="post-title h3 mt-1 mb-3">
                                    <a class="link-dark" href="{{route('article', ['slug' => $article->slug])}}">
                                        {{$article->getLocalTranslation('title')}}
                                    </a>
                                </h2>
                            </div>
                            <div class="post-content">
                                <p class="truncate-3-lines">{{$article->getLocalTranslation('description')}}</p>
                            </div>
                            <!-- /.post-header -->
                            <div class="post-footer">
                                <ul class="post-meta">
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i><span>
                                            {{$article->created_at->format('Y-m-d')}}
                                        </span>
                                    </li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.post-footer -->
                        </article>
                        <!-- /article -->
                    </div>
                    @endforeach
                </div>
                <!--/.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->

        <div class="d-flex justify-content-center mt-4">
              <a href="{{route('articles')}}" class="btn btn-sm btn-primary rounded text-center">
                    {{getLanguageKeyLocalTranslation('index_page_forth_section_cta')}}
              </a>
        </div>
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper bg-light">
    <div class="overflow-hidden">
        <div class="container py-12">
            <div class="row">
                <div class="col-xl-7 col-xxl-6 mx-auto text-center">
                    <h3 class="display-5 mb-4 text-center text-gold">
                        {{getLanguageKeyLocalTranslation('index_page_fifth_section_title')}}
                        <br>
                        {{getLanguageKeyLocalTranslation('index_page_fifth_section_subtitle')}}
                    </h3>
                </div>
                <!--/column -->
            </div>
            <!--/.row -->

            <div class="swiper-container nav-bottom nav-color mb-14" data-margin="30" data-dots="false" data-nav="true" data-items-lg="3" data-items-md="2" data-items-xs="1">
                <div class="swiper overflow-visible pb-2">
                    <div class="swiper-wrapper">
                        @foreach ($albums as $album)   
                            <div class="swiper-slide">
                                <div class="card shadow-lg">
                                    <figure class="album-figure card-img-top overlay overlay-1">
                                        <a href="{{route('album', ['slug' => $album->slug])}}"> 
                                            <img src="{{$album->thumbnailUrl}}" alt="" />
                                        </a>
                                        <figcaption>
                                            <h5 class="from-top mb-0"></h5>
                                        </figcaption>
                                    </figure>
                                </div>
                                <!-- /.card -->
                            </div>
                            <!--/.swiper-slide -->
                        @endforeach
                    </div>
                    <!--/.swiper-wrapper -->
                </div>
                <!-- /.swiper -->
            </div>
            <!-- /.swiper-container -->

            <div class="d-flex justify-content-center mt-2">
              <a href="{{route('albums')}}" class="btn btn-sm btn-primary rounded text-center">
                    {{getLanguageKeyLocalTranslation('index_page_fifth_section_cta')}}
              </a>
            </div>
        </div>
        <!-- /.container -->
    </div>
    <!-- /.overflow-hidden -->
</section>
<!-- /section -->
@endsection
@section('script')
@endsection