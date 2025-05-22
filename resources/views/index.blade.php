@extends('layouts.master')
@section('title', '')
@section('description', '')
@section('canonical', route('index'))
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark">
    <div class="swiper-container swiper-hero dots-over swiper-container-0" data-margin="0" data-autoplay="true" data-autoplaytime="7000" data-nav="true" data-dots="true" data-items="1">
        <div class="swiper swiper-initialized swiper-horizontal swiper-backface-hidden">
            <div class="swiper-wrapper" id="swiper-wrapper-f9c21ed344bd05ba" aria-live="off">
                <div class="swiper-slide bg-overlay bg-overlay-400 bg-dark bg-image swiper-slide-prev" data-image-src="{{ URL::asset('assets/img/photos/banner-1.jpg')}}" role="group" >
                    <div class="container h-100">
                        <div class="row h-100">
                            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                                <h2 class="display-1 fs-48 mb-4 text-white animate__animated animate__slideInDown animate__delay-1s">
                                  Welcome to saud international schools
                                </h2>
                                <p class="lead fs-18 lh-sm mb-7 text-white animate__animated animate__slideInRight animate__delay-2s">                   
                                  Learning Today . . . Leading Tomorrow.                                         
                                </p>
                                <div class="animate__animated animate__slideInUp animate__delay-3s">
                                  <a href="#" class="btn btn-md btn-outline-white rounded">
                                    <i class="uil uil-angle-right"></i>
                                    Learn More
                                  </a>
                                </div>
                            </div>
                            <!--/column -->
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.container -->
                </div>
                <!--/.swiper-slide -->
            </div>
            <!--/.swiper-wrapper -->
            <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
        </div>
        <!-- /.swiper -->
        <div class="swiper-controls">
            <div class="swiper-navigation">
                <div class="swiper-button swiper-button-prev" tabindex="0" role="button" aria-label="Previous slide" aria-controls="swiper-wrapper-f9c21ed344bd05ba" aria-disabled="false"></div>
                <div class="swiper-button swiper-button-next" tabindex="0" role="button" aria-label="Next slide" aria-controls="swiper-wrapper-f9c21ed344bd05ba" aria-disabled="false"></div>
            </div>
            <div class="swiper-pagination swiper-pagination-clickable swiper-pagination-bullets swiper-pagination-horizontal"><span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 1"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active" tabindex="0" role="button" aria-label="Go to slide 2" aria-current="true"></span><span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 3"></span></div>
        </div>
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

            <div class="col-lg-6 mt-8 mt-md-0 text-center text-md-start">
                <h3 class="display-4 mb-2 text-primary">
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
        <div class="row align-items-center">
            <div class="col-lg-3 grades p-0">
                <figure class="overlay caption caption-overlay mb-0">
                    <a href="#"> 
                      <img src="{{ URL::asset('assets/img/photos/grade-1.jpg')}}" style="object-fit: cover" alt="">
                      <span class="bg"></span>
                    </a>

                    <figcaption>
                        <span class="badge badge-lg bg-white text-uppercase mb-3">Kindergarden</span>
                        <h2 class="post-title h3 mt-1 mb-3 text-uppercase">
                          <a href="./blog-post.html">
                            Prek to KG
                          </a>
                        </h2>

                        <ul class="post-meta text-white mb-0">
                          <a href="#" class="btn btn-sm btn-outline-white rounded">Learn More</a>
                        </ul>
                        <!-- /.post-meta -->
                    </figcaption>
                    <!-- /figcaption -->
                </figure>
            </div>

            <div class="col-lg-3 grades p-0">
                <figure class="overlay caption caption-overlay mb-0">
                    <a href="#"> 
                      <img src="{{ URL::asset('assets/img/photos/grade-2.jpg')}}" style="object-fit: cover" alt="">
                      <span class="bg"></span>
                    </a>
                    <figcaption>
                        <span class="badge badge-lg bg-white text-uppercase mb-3">Primary School</span>
                        <h2 class="post-title h3 mt-1 mb-3 text-uppercase">
                          <a href="./blog-post.html">
                            Grades 1-5
                          </a>
                        </h2>
                        <ul class="post-meta text-white mb-0">
                          <a href="#" class="btn btn-sm btn-outline-white rounded">Learn More</a>
                        </ul>
                        <!-- /.post-meta -->
                    </figcaption>
                    <!-- /figcaption -->
                </figure>
            </div>

            <div class="col-lg-3 grades p-0">
                <figure class="overlay caption caption-overlay mb-0">
                    <a href="#"> 
                      <img src="{{ URL::asset('assets/img/photos/grade-3.jpg')}}" style="object-fit: cover" alt="">
                      <span class="bg"></span>
                    </a>

                    <figcaption>
                        <span class="badge badge-lg bg-white text-uppercase mb-3">Middle School</span>
                        <h2 class="post-title h3 mt-1 mb-3 text-uppercase">
                          <a href="./blog-post.html">
                            Grades 6 - 10
                          </a>
                        </h2>
                        <ul class="post-meta text-white mb-0">
                          <a href="#" class="btn btn-sm btn-outline-white rounded">Learn More</a>
                        </ul>
                        <!-- /.post-meta -->
                    </figcaption>
                    <!-- /figcaption -->
                </figure>
            </div>

            <div class="col-lg-3 grades p-0">
                <figure class="overlay caption caption-overlay mb-0">
                    <a href="#"> 
                      <img src="{{ URL::asset('assets/img/photos/grade-4.jpg')}}" style="object-fit: cover" alt="">
                      <span class="bg"></span>
                    </a>
                    <figcaption>
                        <span class="badge badge-lg bg-white text-uppercase mb-3">Secondary School</span>
                        <h2 class="post-title h3 mt-1 mb-3 text-uppercase">
                          <a href="./blog-post.html">
                            Grades 11-12
                          </a>
                        </h2>
                        <ul class="post-meta text-white mb-0">
                          <a href="#" class="btn btn-sm btn-outline-white rounded">Learn More</a>
                        </ul>
                        <!-- /.post-meta -->
                    </figcaption>
                    <!-- /figcaption -->
                </figure>
            </div>
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
                                        <h3 class="counter mb-1 text-gold">7518</h3>
                                        <p class="mb-0 text-light">Projects Done</p>
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
                                        <p class="mb-0 text-light">Projects Done</p>
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
                                        <h3 class="counter mb-1 text-gold">7518</h3>
                                        <p class="mb-0 text-light">Projects Done</p>
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
                                        <h3 class="counter mb-1 text-gold">7518</h3>
                                        <p class="mb-0 text-light">Projects Done</p>
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

            <div class="col-lg-6 mt-8 mt-md-0 text-center text-md-start">
                <h3 class="display-4 mb-2 text-primary">
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

<section class="wrapper bg-light-primary">
    <div class="container py-12">
        <h3 class="display-5 mb-4 text-center text-primary">
          Saud international schools 
          <br>
          Upcoming events
        </h3>

        <div class="swiper-container blog grid-view mb-6" data-margin="30" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <article>
                            <figure class="overlay overlay-1 hover-scale rounded mb-5">
                                <a href="#"> <img src="./assets/img/photos/b4.jpg" alt="" /></a>
                                <figcaption>
                                    <h5 class="from-top mb-0">Read More</h5>
                                </figcaption>
                            </figure>
                            <div class="post-header">
                                <h2 class="post-title h3 mt-1 mb-3"><a class="link-dark" href="./blog-post.html">Ligula tristique quis risus</a></h2>
                            </div>
                            <!-- /.post-header -->
                            <div class="post-footer">
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>14 Apr 2022</span></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.post-footer -->
                        </article>
                        <!-- /article -->
                    </div>
                    <!--/.swiper-slide -->
                    <div class="swiper-slide">
                        <article>
                            <figure class="overlay overlay-1 hover-scale rounded mb-5">
                                <a href="#"> <img src="./assets/img/photos/b4.jpg" alt="" /></a>
                                <figcaption>
                                    <h5 class="from-top mb-0">Read More</h5>
                                </figcaption>
                            </figure>
                            <div class="post-header">
                                <h2 class="post-title h3 mt-1 mb-3"><a class="link-dark" href="./blog-post.html">Ligula tristique quis risus</a></h2>
                            </div>
                            <!-- /.post-header -->
                            <div class="post-footer">
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>14 Apr 2022</span></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.post-footer -->
                        </article>
                        <!-- /article -->
                    </div>

                                        <div class="swiper-slide">
                        <article>
                            <figure class="overlay overlay-1 hover-scale rounded mb-5">
                                <a href="#"> <img src="./assets/img/photos/b4.jpg" alt="" /></a>
                                <figcaption>
                                    <h5 class="from-top mb-0">Read More</h5>
                                </figcaption>
                            </figure>
                            <div class="post-header">
                                <h2 class="post-title h3 mt-1 mb-3"><a class="link-dark" href="./blog-post.html">Ligula tristique quis risus</a></h2>
                            </div>
                            <!-- /.post-header -->
                            <div class="post-footer">
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>14 Apr 2022</span></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.post-footer -->
                        </article>
                        <!-- /article -->
                    </div>
                </div>
                <!--/.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper bg-light">
    <div class="overflow-hidden">
        <div class="container py-12">
            <div class="row">
                <div class="col-xl-7 col-xxl-6 mx-auto text-center">
                  <h3 class="display-5 mb-4 text-center text-primary">
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
                        <div class="swiper-slide">
                            <div class="card shadow-lg">
                                <figure class="card-img-top overlay overlay-1">
                                    <a href="#"> <img src="./assets/img/photos/b12.jpg" alt="" /></a>
                                    <figcaption>
                                        <h5 class="from-top mb-0">Read More</h5>
                                    </figcaption>
                                </figure>
                            </div>
                            <!-- /.card -->
                        </div>
                        <!--/.swiper-slide -->
                        
                        <div class="swiper-slide">
                            <div class="card shadow-lg">
                                <figure class="card-img-top overlay overlay-1">
                                    <a href="#"> <img src="./assets/img/photos/b12.jpg" alt="" /></a>
                                    <figcaption>
                                        <h5 class="from-top mb-0">Read More</h5>
                                    </figcaption>
                                </figure>
                            </div>
                            <!-- /.card -->
                        </div>
                        <!--/.swiper-slide -->

                        <div class="swiper-slide">
                            <div class="card shadow-lg">
                                <figure class="card-img-top overlay overlay-1">
                                    <a href="#"> <img src="./assets/img/photos/b12.jpg" alt="" /></a>
                                    <figcaption>
                                        <h5 class="from-top mb-0">Read More</h5>
                                    </figcaption>
                                </figure>
                            </div>
                            <!-- /.card -->
                        </div>
                        <!--/.swiper-slide -->

                        <div class="swiper-slide">
                            <div class="card shadow-lg">
                                <figure class="card-img-top overlay overlay-1">
                                    <a href="#"> <img src="./assets/img/photos/b12.jpg" alt="" /></a>
                                    <figcaption>
                                        <h5 class="from-top mb-0">Read More</h5>
                                    </figcaption>
                                </figure>
                            </div>
                            <!-- /.card -->
                        </div>
                        <!--/.swiper-slide -->
                    </div>
                    <!--/.swiper-wrapper -->
                </div>
                <!-- /.swiper -->
            </div>
            <!-- /.swiper-container -->

            <div class="d-flex justify-content-center mt-4">
              <a href="#" class="btn btn-sm btn-primary rounded text-center">
                <i class="uil uil-angle-right"></i>
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