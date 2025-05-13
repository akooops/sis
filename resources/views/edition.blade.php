@extends('layouts.master')

@section('title', $edition->name)
@section('description', $edition->description)
@section('canonical', route('edition', ['slug' => $edition->slug]))
@section('image', $edition->event->image->fullpath)

@section('css')
@endsection

@section('content')
<section class="wrapper bg-secondary">
    <div class="container py-12 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <div class="img-mask mask-2">
                    <img src="{{$edition->event->image->fullpath}}" alt="{{$edition->event->name}}" class="bg-white" style="height: 150px; object-fit: contain"/>
                </div>

                <h1 class="display-1 mb-3 mt-4">
                    {{$edition->event->name}}
                </h1>
                <p class="fs-16 text-gray px-2">
                    {{$edition->event->description}}
                </p>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper">
    <div class="container py-12 ">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <h2 class="display-6 mb-4">
                    {{$edition->name}}
                </h2>
                <div class="row gx-0">
                    <div class="col-md-9 text-justify">
                        <p>
                            {{$edition->description}}
                        </p>
                    </div>
                    <!--/column -->
                    <div class="col-md-2 ms-auto">
                        <ul class="list-unstyled">
                            <li>
                                <h5 class="mb-1 text-primary">                                         
                                    <i class="uil uil-calendar-alt"></i>
                                    Durée
                                </h5>

                                <p>
                                    {{$edition->duration}}
                                </p>
                            </li>
                            <li>
                                <h5 class="mb-1 text-primary">
                                    <i class="uil uil-map-marker-alt"></i>
                                    Organiser à
                                </h5>
                                <p>
                                    {{$edition->place}}
                                </p>
                            </li>

                            <li>
                                <h5 class="mb-1 text-primary">
                                    <i class="uil uil-users-alt"></i>
                                    Participants
                                </h5>
                                <p>
                                    {{$edition->participants}}
                                </p>
                            </li>
                        </ul>
                    </div>
                    <!--/column -->
                </div>
                <!--/.row -->
            </div>
            <!-- /column -->
        </div>
    </div>
    <!-- /.container -->

    <div class="container-fluid px-md-6">
        <div class="swiper-container blog grid-view mb-17 mb-md-19" data-margin="30" data-nav="true" data-dots="true" data-items-xxl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper">
                <div class="swiper-wrapper">

                    @foreach($edition->images as $image)
                        <div class="swiper-slide">
                            <figure class="rounded">
                                <figure class="hover-scale rounded cursor-dark">
                                    <a href="{{$image->full_path}}" data-glightbox data-gallery="project-1">
                                        <img src="{{$image->full_path}}" class="bg-white" />
                                    </a>
                                </figure>
                            </figure>
                        </div>
                        <!--/.swiper-slide -->
                    @endforeach
                </div>
                <!--/.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /section -->
@endsection

@section('script')
@endsection