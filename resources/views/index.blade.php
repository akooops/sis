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

                                <div class="d-flex col-lg-5 justify-content-center justify-content-md-end align-items-start" data-cues="slideInDown" data-disabled="true">
                                    <div class="position-relative h-100" data-cue="slideInDown" data-show="true" style="animation-name: slideInDown; animation-duration: 700ms; animation-timing-function: ease; animation-delay: 0ms; animation-direction: normal; animation-fill-mode: both;">
                                        <a href="./assets/media/movie.mp4" class="btn btn-circle btn-primary btn-play ripple position-absolute" style="top:50%; left: 50%; transform: translate(-50%,-50%); z-index:3;" data-glightbox=""><i class="icn-caret-right"></i></a>
                                    </div>
                                    <!-- /div -->
                                </div>
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
                    Welcome to Saud international schools
                </h3>
                
                <p class="mb-4">The mission of Saud International School is to provide a challenging educational environment that meets the needs of the diverse community by offering a worldwide known curriculum implemented with state of the art technology along with  extra-curricular activities.</p>

                <a href="#" class="btn btn-sm btn-primary rounded">
                  <i class="uil uil-angle-right"></i>
                  Learn More
                </a>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
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
                                        <h3 class="counter mb-1 text-gold">4</h3>
                                        <p class="mb-0 text-light">Schools</p>
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
                                        <h3 class="counter mb-1 text-gold">7518</h3>
                                        <p class="mb-0 text-light">Students</p>
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
                                        <h3 class="counter mb-1 text-gold">10000</h3>
                                        <p class="mb-0 text-light">Alumni</p>
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
                                        <h3 class="counter mb-1 text-gold">1st</h3>
                                        <p class="mb-0 text-light">School in saudi</p>
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
                    Saud international schools In number
                </h3>
                <p class="mb-4 text-light">
                  The vision of Saud International School is to create a supportive environment enabling us to equip our students with skills and abilities which will allow them to develop intellectually, physically, socially, emotionally and morally as they become successful and productive lifelong learners in the 21st Century.
                </p>
                <a href="#" class="btn btn-sm btn-primary rounded">
                  Learn More
                </a>
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
                  Message From The Head of School
                </h3>
                
                <p class="mb-4">The mission of Saud International School is to provide a challenging educational environment that meets the needs of the diverse community by offering a worldwide known curriculum implemented with state of the art technology along with  extra-curricular activities.</p>

                <a href="#" class="btn btn-sm btn-primary rounded">
                    Learn More
                </a>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>

<section class="wrapper bg-light-primary">
    <div class="container py-12">
        <h3 class="display-5 mb-4 text-center text-gold">
          Saud international schools 
          <br>
          Latest articles
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
                                    <h5 class="from-top mb-0">Read More</h5>
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
              <a href="#" class="btn btn-sm btn-primary rounded text-center">
                  View more articles
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
                    Saud international schools 
                    <br>
                    Albums
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
                                            <h5 class="from-top mb-0">Read More</h5>
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
              <a href="#" class="btn btn-sm btn-primary rounded text-center">
                  View more albums
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