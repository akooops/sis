@extends('layouts.master')
@section('title', '')
@section('description', '')
@section('canonical', route('index'))
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ URL::asset('assets/img/photos/banner-1.jpg')}}')">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h2 class="display-1 fs-48 mb-4 text-white animate__animated animate__slideInDown animate__delay-1s">
                    Welcome to saud international schools
                </h2>
                <p class="lead fs-18 lh-sm mb-0 text-white animate__animated animate__slideInRight animate__delay-2s">                   
                    Learning Today . . . Leading Tomorrow.                                         
                </p>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

<section class="wrapper bg-light-primary">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            <li class="nav-item text-nowrap">
                <a class="nav-link py-2 text-uppercase fs-14 text-center active fw-semibold" href="">ACADEMICS OVERVIEW</a>
            </li>
            <li class="nav-item text-nowrap">
                <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold" href="">IB FRAMEWORK</a>
            </li>
            <li class="nav-item text-nowrap">
                <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold" href="">Early Years Programme Pre-k To KG2</a>
            </li>
            <li class="nav-item text-nowrap">
                <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold" href="">PRIMARY GRADES 1 – 5</a>
            </li>
            <li class="nav-item text-nowrap">
                <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold" href="">fdsfds</a>
            </li>
        </ul>
    </div>
</section>

<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a class="text-uppercase" href="#">Home</a></li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">Shop</li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper bg-light">
    <div class="container pt-6 pb-12">
        <div class="row gx-lg-8 gx-xl-12">
            <div class="col-lg-8">
                <div class="blog classic-view">
                    <article class="post">
                        <div class="card">
                            <figure class="card-img-top overlay overlay-1 hover-scale">
                                <a href="./blog-post.html"><img src="./assets/img/photos/b1.jpg" alt=""><span class="bg"></span></a>
                                <figcaption>
                                    <h5 class="from-top mb-0">Read More</h5>
                                </figcaption>
                            </figure>
                            <div class="card-body">
                                <div class="post-header">
                                    <h2 class="post-title mt-1 mb-0"><a class="link-dark" href="./blog-post.html">Amet Dolor Bibendum Parturient Cursus</a></h2>
                                </div>
                                <!-- /.post-header -->
                                <div class="post-content">
                                    <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Nullam quis risus eget urna mollis ornare vel. Nulla vitae elit libero, a pharetra augue. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Sed posuere consectetur est at lobortis. Cras mattis consectetur purus sit amet fermentum. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh. Cras mattis consectetur purus.</p>
                                </div>
                                <!-- /.post-content -->
                            </div>
                            <!--/.card-body -->
                            <div class="card-footer">
                                <ul class="post-meta d-flex mb-0">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>5 Jul 2022</span></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.card-footer -->
                        </div>
                        <!-- /.card -->
                    </article>
                    <!-- /.post -->
                </div>
                <!-- /.blog -->
            </div>
            <!-- /column -->
            <aside class="col-lg-4 sidebar">
                <div class="widget">
                    <form class="search-form">
                        <div class="form-floating mb-0">
                            <input id="search-form" type="text" class="form-control" placeholder="Search">
                            <label for="search-form">Search</label>
                        </div>
                    </form>
                    <!-- /.search-form -->
                </div>
                <!-- /.widget -->

                <div class="widget">
                    <h4 class="widget-title mb-3">Popular Posts</h4>
                    <ul class="image-list">
                        <li>
                            <figure class="rounded"><a href="./blog-post.html"><img src="./assets/img/photos/a1.jpg" alt=""></a></figure>
                            <div class="post-content">
                                <h6 class="mb-2"> <a class="link-dark" href="./blog-post.html">Magna Mollis Ultricies</a> </h6>
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>26 Mar 2022</span></li>
                                    <li class="post-comments"><a href="#"><i class="uil uil-comment"></i>3</a></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                        </li>
                        <li>
                            <figure class="rounded"> <a href="./blog-post.html"><img src="./assets/img/photos/a2.jpg" alt=""></a></figure>
                            <div class="post-content">
                                <h6 class="mb-2"> <a class="link-dark" href="./blog-post.html">Ornare Nullam Risus</a> </h6>
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>16 Feb 2022</span></li>
                                    <li class="post-comments"><a href="#"><i class="uil uil-comment"></i>6</a></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                        </li>
                        <li>
                            <figure class="rounded"><a href="./blog-post.html"><img src="./assets/img/photos/a3.jpg" alt=""></a></figure>
                            <div class="post-content">
                                <h6 class="mb-2"> <a class="link-dark" href="./blog-post.html">Euismod Nullam Fusce</a> </h6>
                                <ul class="post-meta">
                                    <li class="post-date"><i class="uil uil-calendar-alt"></i><span>8 Jan 2022</span></li>
                                    <li class="post-comments"><a href="#"><i class="uil uil-comment"></i>5</a></li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                        </li>
                    </ul>
                    <!-- /.image-list -->
                </div>
                <!-- /.widget -->
            </aside>
            <!-- /column .sidebar -->
        </div>
        <!-- /.row -->
    </div>
</section>
@endsection
@section('script')
@endsection