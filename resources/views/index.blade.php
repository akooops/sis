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
        data-autoplaytime="60000" 
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
                    {{getLanguageKeyLocalTranslation('index_page_welcome_section_title')}}
                </h2>
                
                <p class="mt-4 mb-8" data-aos="fade-left" data-aos-duration="1500">
                    {{getLanguageKeyLocalTranslation('index_page_welcome_section_subtitle')}}
                </p>
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
                {{getLanguageKeyLocalTranslation('index_page_academics_levels_section_title')}}
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

                                    @php
                                        $subtitle = $program->getLocalTranslation('subtitle');
                                    @endphp
                                    @if(!empty($subtitle) && trim(strip_tags($subtitle)) !== '' && $subtitle !== "\u{200e}")
                                        <h3 class="px-0 py-0">
                                            {{ $subtitle }}
                                        </h3>
                                    @endif

                                    <a href="{{route('program', ['slug' => $program->slug])}}" 
                                        class="btn py-1">
                                        <i class="uil uil-angle-right-b me-2"></i>

                                        {{getLanguageKeyLocalTranslation('index_page_academics_levels_section_cta')}}
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

<section class="wrapper articles-section mb-8">
    <div class="container pb-12">
        <div class="row text-center mb-6">
            <h2 class="text-primary" data-aos="fade-up" data-aos-duration="1000">
                {{getLanguageKeyLocalTranslation('index_page_articles_section_title')}}
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
                    @foreach ($articles as $article)   
                    <div class="swiper-slide">
                        <figure class="hover-scale mb-5">
                            <a href="{{route('article', ['slug' => $article->slug])}}">
                                <img src="{{$article->thumbnailUrl}}" alt="" />
                            </a>
                        </figure>

                        <div class="px-8">
                            <h3 class="pt-8 pb-2">
                                <a href="{{route('article', ['slug' => $article->slug])}}">
                                    {{$article->getLocalTranslation('title')}}
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

        <div class="d-flex justify-content-center px-0 px-lg-8">
              <a href="{{route('articles')}}" class="btn btn-primary rounded text-center">
                    <i class="uil uil-angle-right-b me-2"></i>
                    {{getLanguageKeyLocalTranslation('index_page_articles_section_cta')}}
              </a>
        </div>
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper achievements-section">
    <div class="container">
        <div class="row text-center mb-6">
            <h2 class="text-primary" data-aos="fade-up" data-aos-duration="1000">
                {{getLanguageKeyLocalTranslation('index_page_achievements_section_title')}}
            </h2>
        </div>

        <div class="swiper-container px-8" 
            data-margin="20" 
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
                    @foreach ($achievements as $achievement)   
                    <div class="swiper-slide">
                        <div class="card">
                            @if($achievement->thumbnailUrl)
                                <figure class="hover-scale">
                                    <a href="{{ $achievement->url }}">
                                        <img src="{{ $achievement->thumbnailUrl }}" alt="{{ $achievement->getLocalTranslation('title') }}" />
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body">
                                @if($achievement->category)
                                    <span class="badge bg-primary rounded py-1 mb-2">
                                        {{ $achievement->category->getLocalTranslation('title') }}
                                    </span>
                                @endif
                                <h2>
                                    <a href="{{ $achievement->url }}">
                                        {{ $achievement->getLocalTranslation('title') }}
                                    </a>
                                </h2>

                                <p class="mb-0">
                                    {{ $achievement->getLocalTranslation('description') }}
                                </p>
                            </div>
                            <!--/.card-body -->
                            
                            <div class="card-footer">
                                <ul class="post-meta d-flex mb-0">
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i>
                                        <span>{{ \Carbon\Carbon::parse($achievement->achievement_date)->format('Y-m-d') }}</span>
                                    </li>
                                    @if($achievement->getLocalTranslation('done_by'))
                                        <li class="ms-3">
                                            <i class="uil uil-user"></i>
                                            <span>{{ $achievement->getLocalTranslation('done_by') }}</span>
                                        </li>
                                    @endif
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.card-footer -->
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

        <div class="d-flex justify-content-center px-0 px-lg-8">
            <a href="{{route('achievements')}}" class="btn btn-primary rounded text-center">
                <i class="uil uil-angle-right-b me-2"></i>
                {{getLanguageKeyLocalTranslation('index_page_achievements_section_cta')}}
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
                    {{getLanguageKeyLocalTranslation('index_page_albums_section_title')}}
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
                                    <a href="{{route('album', ['slug' => $album->slug])}}">
                                        <img src="{{$album->thumbnailUrl}}" alt="{{$album->getLocalTranslation('title')}}" />
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
                    {{getLanguageKeyLocalTranslation('index_page_albums_section_cta')}}
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