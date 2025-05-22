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
<div class="container py-12 py-md-5">
    hgfhgfhfg

    <div class="row pt-6">
        <div id="accordion-3" class="accordion-wrapper">
            <div class="card accordion-item">
                <div class="card-header" id="accordion-heading-3-1">
                    <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#grades-collapse" aria-expanded="false" aria-controls="grades-collapse">
                        Files
                    </button>
                </div>
                <!-- /.card-header -->
                <div id="grades-collapse" class="collapse">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col" width="25px">#</th>
                                    <th scope="col" width="90%">File name</th>
                                    <th scope="col">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">1</th>
                                    <td>Mark</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary rounded">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">2</th>
                                    <td>Jacob</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.collapse -->
            </div>
        </div>
    </div>
    <!-- /.container -->
</div>
</section>

@endsection
@section('script')
@endsection