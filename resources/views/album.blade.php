@extends('layouts.master')
@section('title', $album->getLocalTranslation('title'))
@section('description', $album->getLocalTranslation('description'))
@section('canonical', route('albums', ['slug' => $album->slug]))
@section('image', $album->thumbnailUrl)
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $album->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$album->getLocalTranslation('title')}}
                </h1>

            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a class="text-uppercase" href="#">Albums</a></li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$album->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper bg-light">
    <div class="container pt-6 pb-12">
        <div class="row">
            <h2 class="display-5 mb-3">
                {{$album->getLocalTranslation('title')}}
            </h2>

            <p class="lead fs-md">
                {{$album->getLocalTranslation('description')}}
            </p>

            <hr class="mt-2 mb-4">
        </div>

        <div class="row mt-5 gx-md-6 gy-6">

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