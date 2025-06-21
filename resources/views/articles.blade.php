@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('articles'))
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
                    <div class="banner-img" style="background-image: url('{{ $page->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$page->getLocalTranslation('title')}}
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


@if($page->menu)
<section class="wrapper page-menu-section">
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
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper">
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


<section class="wrapper">
    <div class="container pt-6 pb-12 articles-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="row">
            <div class="col-lg-8">
                <div class="row gy-4 mb-8">
                    @foreach ($articles as $article)
                        <div class="col-md-6">
                            <div class="card">
                                <figure class="hover-scale">
                                    <a href="{{route('article', ['slug' => $article->slug])}}">
                                        <img src="{{$article->thumbnailUrl}}" alt="" />
                                    </a>
                                </figure>

                                <div class="card-body">
                                    <h2>
                                        <a href="{{route('article', ['slug' => $article->slug])}}">
                                            {{$article->getLocalTranslation('title')}}
                                        </a>
                                    </h2>

                                    <p class="truncate-3-lines mb-0">
                                        {{$article->getLocalTranslation('description')}}
                                    </p>
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
                        <!-- /.post -->
                    @endforeach
                </div>
                <!-- /.row -->
                                
                <nav class="d-flex" aria-label="pagination">
                    <ul class="pagination">
                        <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
                            <a class="page-link" href="{{ route('articles', array_merge(['page' => $pagination["prevPage"]], request()->only('search'))) }}" aria-label="Previous">
                            <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
                            </a>
                        </li>

                        @foreach($pagination["pages"] as $page)
                            <li class="page-item">
                                <a class="page-link {{($page == $pagination['currentPage']) ? 'active' : ''}}" href="{{ route('articles', array_merge(['page' => $page], request()->only('search'))) }}">
                                    {{$page}}
                                </a>
                            </li>
                        @endforeach

                        <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                            <a class="page-link" href="{{ route('articles', array_merge(['page' => $pagination["nextPage"]], request()->only('search'))) }}" aria-label="Next">
                            <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
                            </a>
                        </li>
                    </ul>
                    <!-- /.pagination -->
                </nav>
                <!-- /nav -->
            </div>
            <!-- /column -->

            <aside class="col-lg-4 sidebar">
                <div class="widget">
                    <form class="search-form">
                        <div class="mb-0">
                            <form action="{{route('articles')}}">
                                <input name="search" value="{{request()->get('search')}}" id="search-form" type="text" class="form-control">
                            </form>  
                        </div>
                    </form>
                    <!-- /.search-form -->
                </div>
                <!-- /.widget -->

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