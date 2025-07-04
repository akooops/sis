@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('articles'))
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
        <div class="row gx-lg-8 gx-xl-12">
            <div class="col-lg-8">
                <div class="blog grid grid-view">
                    <div class="row isotope gx-md-8 gy-8 mb-8">
                        @foreach ($articles as $article)
                            <article class="item post col-md-6">
                                <div class="card">
                                    <figure class="article-figure card-img-top overlay overlay-1 hover-scale">
                                        <a href="{{route('article', ['slug' => $article->slug])}}">
                                            <img src="{{$article->thumbnailUrl}}" alt="" />
                                        </a>
                                        <figcaption>
                                            <h5 class="from-top mb-0"></h5>
                                        </figcaption>
                                    </figure>
                                    <div class="card-body">
                                        <div class="post-header">
                                            <h2 class="post-title h3 mt-1 mb-3">
                                                <a class="link-dark" href="{{route('article', ['slug' => $article->slug])}}">
                                                    {{$article->getLocalTranslation('title')}}
                                                </a>
                                            </h2>
                                        </div>
                                        <!-- /.post-header -->
                                        <div class="post-content">
                                            <p class="truncate-3-lines">{{$article->getLocalTranslation('description')}}</p>
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
                        @endforeach
                    </div>
                    <!-- /.row -->
                </div>
                                
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
                        <div class="form-floating mb-0">
                            <form action="{{route('articles')}}">
                                <input name="search" value="{{request()->get('search')}}" id="search-form" type="text" class="form-control" placeholder="Search">
                                <label for="search-form">
                                    {{getLanguageKeyLocalTranslation('sidebar_search_form_placeholder')}}
                                </label>
                            </form>  
                        </div>
                    </form>
                    <!-- /.search-form -->
                </div>
                <!-- /.widget -->

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