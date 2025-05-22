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
        <div class="row">
            <p class="mb-0">
                The mission of Saud International School is to provide a challenging educational environment that meets the needs of the diverse community by offering a worldwide known curriculum implemented with state of the art technology along with  extra-curricular activities.
            </p>
        </div>

        <div class="row mt-5 gx-md-6 gy-6">
            <div class="item col-12 col-sm-6 col-md-4">
                <figure class="hover-scale rounded cursor-dark"><a href="./assets/img/photos/pp2.jpg" data-glightbox="" data-gallery="project-1"><img src="./assets/img/photos/pp2.jpg" alt=""></a></figure>
            </div>
            <!--/column -->
            <div class="item col-12 col-sm-6 col-md-4">
                <figure class="hover-scale rounded cursor-dark"><a href="./assets/img/photos/pp3.jpg" data-glightbox="" data-gallery="project-1"><img src="./assets/img/photos/pp3.jpg" alt=""></a></figure>
            </div>
            <!--/column -->
            <div class="item col-12 col-sm-6 col-md-4">
                <figure class="hover-scale rounded cursor-dark"><a href="./assets/img/photos/pp4.jpg" data-glightbox="" data-gallery="project-1"><img src="./assets/img/photos/pp4.jpg" alt=""></a></figure>
            </div>
            <!--/column -->
            <div class="item col-12 col-sm-6 col-md-4">
                <figure class="hover-scale rounded cursor-dark"><a href="./assets/img/photos/pp5.jpg" data-glightbox="" data-gallery="project-1"><img src="./assets/img/photos/pp5.jpg" alt=""></a></figure>
            </div>
            <!--/column -->
        </div>
    </div>
</section>
@endsection
@section('script')
@endsection