@extends('layouts.master')
@section('title', $article->getLocalTranslation('title'))
@section('description', $article->getLocalTranslation('description'))
@section('canonical', route('articles', ['slug' => $article->slug]))
@section('image', $article->thumbnailUrl)
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
        data-items="1">
        
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $article->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$article->getLocalTranslation('title')}}
                                    </h1>
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
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->

<section class="wrapper">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('articles')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_articles_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$article->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper">
    <div class="container pt-6 pb-12 articles-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <figure class="hover-scale">
                        <img src="{{$article->thumbnailUrl}}" alt="{{$article->getLocalTranslation('title')}}">
                    </figure>

                    <div class="card-body">
                        <h2>
                            {{$article->getLocalTranslation('title')}}
                        </h2>

                        <p>
                            {{$article->getLocalTranslation('description')}}
                        </p>

                        <hr class="mt-2 mb-4">

                        <div class="post-content">
                            <div class="w-100">
                                <x-markdown>
                                    {{ $article->getLocalTranslation('content') }}
                                </x-markdown>
                            </div>
                        </div>
                        <!-- /.post-content -->
                    </div>
                    <!--/.card-body -->
                    <div class="card-footer">
                        <ul class="post-meta d-flex mb-0">
                            <li class="post-date">
                                <i class="uil uil-calendar-alt"></i>
                                <span>{{$article->created_at->format('Y-m-d')}}</span>
                            </li>
                        </ul>
                        <!-- /.post-meta -->
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /column -->

            <aside class="col-lg-4 sidebar">
                <div class="widget popular-articles-container ">
                    <h4 class="widget-title mb-3">
                        {{getLanguageKeyLocalTranslation('sidebar_popular_article_title')}}
                    </h4>
                    
                    <ul class="image-list">
                        @foreach ($popularArticles as $popularArticle)
                            <li>
                                <figure>
                                    <a href="{{route('article', ['slug' => $popularArticle->slug])}}">
                                        <img src="{{$popularArticle->thumbnailUrl}}" alt="{{$popularArticle->getLocalTranslation('title')}}" />
                                    </a>
                                </figure>

                                <div class="post-content">
                                    <h6 class="mb-2"> 
                                        <a class="link-dark" href="{{route('article', ['slug' => $popularArticle->slug])}}">
                                            {{$popularArticle->getLocalTranslation('title')}}
                                        </a>
                                    </h6>
                                    <ul class="post-meta">
                                        <li class="post-date">
                                            <i class="uil uil-calendar-alt"></i>
                                            <span>{{$popularArticle->created_at->format('Y-m-d')}}</span>
                                        </li>
                                    </ul>
                                    <!-- /.post-meta -->
                                </div>
                            </li>
                        @endforeach
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