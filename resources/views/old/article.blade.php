@extends('layouts.master')
@section('title', $article->getLocalTranslation('title'))
@section('description', $article->getLocalTranslation('description'))
@section('canonical', route('articles', ['slug' => $article->slug]))
@section('image', $article->thumbnailUrl)
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $article->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$article->getLocalTranslation('title')}}
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


<section class="wrapper bg-light">
    <div class="container pt-6 pb-12">
        <div class="row gx-lg-8 gx-xl-12">
            <div class="col-lg-8">
                <div class="blog classic-view">
                    <article class="post">
                        <div class="card">
                            <figure class="article-figure card-img-top overlay overlay-1 hover-scale" style="height: 500px">
                                <img src="{{$article->thumbnailUrl}}" alt="" style="height: 500px !important; object-fit: cover"><span class="bg"></span>
                            </figure>


                            <div class="card-body">
                                <div class="post-header">
                                    <h2 class="post-title mt-1 mb-3">
                                        {{$article->getLocalTranslation('title')}}
                                    </h2>

                                    <p class="lead fs-md">
                                        {{$article->getLocalTranslation('description')}}
                                    </p>

                                    <hr class="mt-2 mb-4">
                                </div>
                                <!-- /.post-header -->
                                <div class="post-content">
                                    <div class="w-100 page-content">
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
                    </article>
                    <!-- /.post -->
                </div>
                <!-- /.blog -->
            </div>
            <!-- /column -->

            <aside class="col-lg-4 sidebar">
                <div class="widget">
                    <h4 class="widget-title mb-3">
                        {{getLanguageKeyLocalTranslation('sidebar_popular_article_title')}}
                    </h4>
                    <ul class="image-list">
                        @foreach ($popularArticles as $popularArticle)
                            <li>
                                <figure class="popular-article-figure rounded">
                                    <a href="{{route('article', ['slug' => $popularArticle->slug])}}">
                                        <img src="{{$popularArticle->thumbnailUrl}}" alt="" />
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