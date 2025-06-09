@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('visits'))
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $page->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$page->getLocalTranslation('title')}}
                </h1>

            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

@if($page->menu)
<section class="wrapper bg-light-primary">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($page->menu->items as $menuItem)
                @php
                    $menuUrl = $menuItem->page
                        ? route('page', ['slug' => $menuItem->page->slug])
                        : $menuItem->url;
                    $currentUrl = url()->current();
                @endphp

                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('index')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_index_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$page->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper bg-light">
    <div class="container pt-6 pb-12">
        <div class="row gx-8">
                @foreach ($visitServices as $visitService)
                    <div class="col-sm-6 col-md-4">
                        <div class="card">
                            <figure class="visit-service-figure card-img-top overlay overlay-1 hover-scale">
                                <img src="{{$visitService->thumbnailUrl}}" alt="" />
                            </figure>
                            <div class="card-body p-4">
                                <div class="post-header">
                                    <h2 class="post-title h3 mb-3">
                                        {{$visitService->getLocalTranslation('title')}}
                                    </h2>
                                </div>
                                <!-- /.post-header -->
                                <div class="post-content mb-3">
                                    <p class="truncate-3-lines mb-2">{{$visitService->getLocalTranslation('description')}}</p>

                                    <a href="#" data-bs-toggle="modal" data-bs-target="#visit-service-{{$visitService->id}}">
                                        {{getLanguageKeyLocalTranslation('visits_page_read_more_cta')}}
                                    </a>
                                </div>
                                <!-- /.post-content -->
                                
                                <div class="post-footer">
                                    <ul class="post-meta d-flex mb-0">
                                        <li class="post-date">
                                            <i class="uil uil-clock"></i>
                                            <span>
                                                {{$visitService->formattedDuration}}
                                            </span>
                                        </li>
                                    </ul>

                                    <div class="d-flex my-2">
                                        <input type="number" min="1" max="{{$visitService->capacity}}" class="form-control" value="1">
                                        <button class="btn btn-primary ms-2">
                                            {{getLanguageKeyLocalTranslation('visits_page_select_button')}}
                                        </button>
                                    </div>
                                    <!-- /.form-floating -->
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.post -->

                    <div class="modal fade" id="visit-service-{{$visitService->id}}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-md">
                            <div class="modal-content">
                                <div class="modal-body p-0">
                                    <button class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    <div class="row">
                                        <div>
                                            <figure class="visit-service-figure">
                                                <img src="{{$visitService->thumbnailUrl}}" alt="" />
                                            </figure>
                                        </div>
                                        <!-- /column -->
                                    </div>
                                    <!-- /.row -->

                                    <div class="px-6 py-2">
                                        <h3>
                                            {{$visitService->getLocalTranslation('title')}}
                                        </h3>

                                        <p class="lead">
                                            {{$visitService->getLocalTranslation('description')}}
                                        </p>

                                        <hr class="mt-2 mb-4">

                                        <p class="mb-6">
                                            <x-markdown>
                                                {{ $visitService->getLocalTranslation('content') }}
                                            </x-markdown>
                                        </p>
                                    </div>

                                </div>
                                <!--/.modal-body -->
                            </div>
                            <!--/.modal-content -->
                        </div>
                        <!--/.modal-dialog -->
                    </div>
                    <!--/.modal -->
                @endforeach
            </div>
            <!-- /.row -->
    </div>
</section>
@endsection
@section('script')
@endsection