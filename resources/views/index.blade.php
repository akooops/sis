@php
    $firstSectionCtaPageSetting = getSetting('index_page_first_section_cta_page');
    $firstSectionCtaPage = null;

    if($firstSectionCtaPageSetting) $firstSectionCtaPage = getPage($firstSectionCtaPageSetting->value);

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

<section class="wrapper banners-section">
    <div class="swiper-container" 
        data-margin="0" 
        data-autoplay="true" 
        data-autoplaytime="7000" 
        data-nav="true" 
        data-dots="true" 
        data-items="1"
        data-loop="true">
        
        <div class="swiper">
            <div class="swiper-wrapper">

                @foreach ($banners as $banner)
                    <div class="swiper-slide bg-overlay">
                        
                        @if($banner->video)
                            <!-- Video Background -->
                            <video class="banner-video" autoplay muted loop playsinline>
                                <source src="{{ $banner->videoUrl }}">

                                <!-- Fallback to image if video fails -->
                                <img src="{{ $banner->thumbnailUrl }}" alt="{{$banner->getLocalTranslation('title')}}">
                            </video>
                        @else
                            <!-- Image Background -->
                            <div class="banner-img" style="background-image: url('{{ $banner->thumbnailUrl }}')"></div>
                        @endif

                        <div class="container h-100">
                            <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                                <div class="row px-0 px-lg-14">
                                    <div class="col-12 col-lg-8 px-0">
                                        <h2 class="mb-0">
                                            {{$banner->getLocalTranslation('title')}}
                                        </h2>
                                    </div>
                                    <!--/.col -->

                                    <div class="d-flex col-12 col-lg-4 align-items-start mt-4 mt-lg-0 px-0 px-lg-16">
                                        <a href="{{$banner->page ? route('page', ['slug' => $banner->page->slug]) : $banner->url}}" 
                                            class="btn py-1 px-16">
                                            <i class="uil uil-angle-right-b me-2"></i>

                                            {{$banner->getLocalTranslation('cta')}}
                                        </a>
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
                @endforeach
            
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->

<section class="wrapper welcome-section">
    <div class="container pt-12 pb-6">
        <div class="row align-items-center">
            <div class="col-lg-6 position-relative" data-aos="fade-right" data-aos-duration="1000">
                <div class="shape rellax"></div>
                
                <figure>
                    <img class="ms-4 ms-sm-6" src="{{ URL::asset('assets/img/home-1-welcome.png')}}" alt="">
                </figure>
            </div>
            <!--/column -->

            <div class="col-lg-6 mt-12 mt-lg-0 text-center text-lg-start">
                <h2 class="text-primary mb-0" data-aos="fade-left" data-aos-duration="1000">
                    {{getLanguageKeyLocalTranslation('index_page_first_section_title')}}
                </h2>
                
                <p class="mt-4 mb-8" data-aos="fade-left" data-aos-duration="1500">
                    {{getLanguageKeyLocalTranslation('index_page_first_section_subtitle')}}
                </p>

                @if($firstSectionCtaPage)
                    <a href="{{route('page', ['slug' => $firstSectionCtaPage->slug])}}" class="btn btn-primary rounded" data-aos="fade-left" data-aos-duration="2000">
                        <i class="uil uil-angle-right-b me-2"></i>
                        <span>
                            {{getLanguageKeyLocalTranslation('index_page_first_section_cta')}}
                        </span>
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

<section class="wrapper divider-section">
    <div class="container pb-8">
        <div class="row" data-aos="fade-up" data-aos-duration="1000">
            <div class="divider-line"></div>

            <div class="d-flex justify-content-center" data-aos="zoom-in" data-aos-duration="2000">
                <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="{{getLanguageKeyLocalTranslation('website_title')}}">              
            </div>
        </div>
    </div>
</section>
<!-- /section -->

<section class="wrapper grades-section">
    <div class="container">
        <div class="row text-center mb-6">
            <h2 class="text-primary" data-aos="fade-up" data-aos-duration="1000">
                {{getLanguageKeyLocalTranslation('index_page_second_section_title')}}
            </h2>
        </div>
    </div>
    <!-- /.container -->

    <div class="swiper-container"
        data-margin="0" 
        data-items-xl="5" 
        data-items-md="2" 
        data-items-xs="1"
        
        data-aos="fade-up" 
        data-aos-duration="2000">

        <div class="swiper">
            <div class="swiper-wrapper">

                @foreach ($programs as $program)
                    <div class="swiper-slide bg-overlay">
                        <div class="grade-img" style="background-image: url('{{ $program->thumbnailUrl }}')"></div>

                        <div class="container h-100">
                            <div class="row h-100 align-items-end pb-8 pb-lg-12">           
                                <div class="col-12 px-8">
                                    <p class="px-0">
                                        {{$program->getLocalTranslation('title')}}
                                    </p>

                                    <h3 class="px-0">
                                        {{$program->getLocalTranslation('subtitle')}}
                                    </h3>

                                    <a href="{{route('program', ['slug' => $program->slug])}}" 
                                        class="btn py-1">
                                        <i class="uil uil-angle-right-b me-2"></i>

                                        {{getLanguageKeyLocalTranslation('index_page_second_section_cta')}}
                                    </a>
                                </div>     
                                <!--/.row -->      
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

<section class="wrapper facts-section">
    <div class="container h-100 py-12">
        <div class="row h-100 align-items-center">
            <div class="col-lg-5 pe-0 pe-lg-16 mb-8 mb-lg-0 facts-content-container" data-aos="fade-right" data-aos-duration="2000">
                <h3 data-aos="fade-up" data-aos-duration="1500">
                    {{getLanguageKeyLocalTranslation('index_page_third_section_title')}}
                </h3>

                <p class="mt-4 mb-8 text-light">
                   {{getLanguageKeyLocalTranslation('index_page_third_section_subtitle')}}
                </p>

                @if($thirdSectionCtaPage)
                    <a href="{{route('page', ['slug' => $thirdSectionCtaPage->slug])}}" 
                        class="btn py-1 px-8">
                        <i class="uil uil-angle-right-b me-2"></i>
                        {{getLanguageKeyLocalTranslation('index_page_third_section_cta')}}
                    </a>
                @endif
            </div>
            <!--/column -->

            <div class="col-lg-7 grid facts-numbers-container" data-aos="fade-left" data-aos-duration="3000">
                <div class="row gx-md-5 gy-5 align-items-center counter-wrapper isotope">
                    <div class="item col-md-6 px-2 py-4">
                        <div class="d-flex flex-row">
                            <div class="icon btn btn-circle me-4"> 
                                <i class="uil uil-presentation-check"></i> 
                            </div>

                            <div>
                                <h4 class="counter">
                                    {{getLanguageKeyLocalTranslation('index_page_first_number_value')}}
                                </h4>

                                <p>
                                    {{getLanguageKeyLocalTranslation('index_page_first_number_title')}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--/column -->

                    <div class="item col-md-6 px-2 py-4">
                        <div class="d-flex flex-row">
                            <div class="icon btn btn-circle me-4"> 
                                <i class="uil uil-users-alt"></i> 
                            </div>

                            <div>
                                <h4 class="counter">
                                    {{getLanguageKeyLocalTranslation('index_page_second_number_value')}}
                                </h4>

                                <p>
                                    {{getLanguageKeyLocalTranslation('index_page_second_number_title')}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--/column -->

                    <div class="item col-md-6 px-2 py-4">
                        <div class="d-flex flex-row">
                            <div class="icon btn btn-circle me-4"> 
                                <i class="uil uil-user-check"></i> 
                            </div>

                            <div>
                                <h4 class="counter">
                                    {{getLanguageKeyLocalTranslation('index_page_third_number_value')}}
                                </h4>

                                <p>
                                    {{getLanguageKeyLocalTranslation('index_page_third_number_title')}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--/column -->

                    <div class="item col-md-6 px-2 py-4">
                        <div class="d-flex flex-row">
                            <div class="icon btn btn-circle me-4"> 
                                <i class="uil uil-trophy"></i> 
                            </div>

                            <div>
                                <h4 class="counter">
                                    {{getLanguageKeyLocalTranslation('index_page_forth_number_value')}}
                                </h4>

                                <p>
                                    {{getLanguageKeyLocalTranslation('index_page_forth_number_title')}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--/column -->
                </div>
                <!--/.row -->
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper divider-section pt-8">
    <div class="container pb-8">
        <div class="row" data-aos="fade-up" data-aos-duration="1000">
            <div class="divider-line"></div>

            <div class="d-flex justify-content-center" data-aos="zoom-in" data-aos-duration="2000">
                <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="{{getLanguageKeyLocalTranslation('website_title')}}">              
            </div>
        </div>
    </div>
</section>
<!-- /section -->

<section class="wrapper events-section mb-8">
    <div class="container pb-12">
        <div class="row text-center mb-6">
            <h2 class="text-primary" data-aos="fade-up" data-aos-duration="1000">
                {{getLanguageKeyLocalTranslation('index_page_forth_section_title')}}
            </h2>
        </div>

        <div class="swiper-container px-8" 
            data-margin="10" 
            data-dots="false" 
            data-autoplay="true" 
            data-autoplaytime="7000" 
            data-items-xl="3" 
            data-items-md="2" 
            data-items-xs="1"
            data-aos="fade-up" 
            data-aos-duration="2000">

            <div class="swiper mb-8">
                <div class="swiper-wrapper">
                    @foreach ($events as $event)   
                    <div class="swiper-slide">
                        <figure class="hover-scale mb-5">
                            <a href="{{route('event', ['slug' => $event->slug])}}">
                                <img src="{{$event->thumbnailUrl}}" alt="" />
                            </a>
                        </figure>

                        <div class="px-8">
                            <h3 class="pt-8 pb-2">
                                <a href="{{route('event', ['slug' => $event->slug])}}">
                                    {{$event->getLocalTranslation('title')}}
                                </a>
                            </h3>

                            <span class="arrow">⟶</span>      
                        </div>
                    </div>
                    @endforeach
                </div>
                <!--/.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->

        <div class="d-flex justify-content-center justify-content-lg-end px-0 px-lg-8">
              <a href="{{route('events')}}" class="btn btn-primary rounded text-center">
                    <i class="uil uil-angle-right-b me-2"></i>
                    {{getLanguageKeyLocalTranslation('index_page_forth_section_cta')}}
              </a>
        </div>
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper albums-section">
    <div class="overflow-hidden">
        <div class="container py-18">
            <div class="row text-center mb-6">
                <h2 class="text-primary" data-aos="fade-up" data-aos-duration="1000">
                    {{getLanguageKeyLocalTranslation('index_page_fifth_section_title')}}
                </h2>
            </div>

            <div class="swiper-container mb-8" 
                data-margin="30" 
                data-autoplay="true" 
                data-autoplaytime="2000" 
                data-dots="false" 
                data-nav="false" 
                data-items-lg="4"
                data-items-md="2" 
                data-items-xs="1"
                data-aos="fade-up" 
                data-aos-duration="2000">

                <div class="swiper overflow-visible">
                    <div class="swiper-wrapper">
                        @foreach ($albums as $album)   
                            <div class="swiper-slide">
                                <figure class="hover-scale">
                                    <img src="{{$album->thumbnailUrl}}" alt="{{$album->getLocalTranslation('title')}}" />
                                    <a class="item-link" href="{{route('album', ['slug' => $album->slug])}}">
                                        <i class="uil uil-link"></i>
                                    </a>
                                </figure>
                            </div>
                        @endforeach
                    </div>
                    <!--/.swiper-wrapper -->
                </div>
                <!--/.swiper -->
            </div>
            <!-- /.swiper-container -->

            <div class="d-flex justify-content-center px-0 px-lg-8">
                <a href="{{route('albums')}}" class="btn btn-primary rounded text-center">
                    <i class="uil uil-angle-right-b me-2"></i>
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