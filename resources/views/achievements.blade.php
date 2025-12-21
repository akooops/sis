@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('achievements'))
@section('css')
<style>
    .avatar.object-cover {
        object-fit: cover;
    }
</style>
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
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}" href="{{ $menuUrl }}">
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

<section class="wrapper page-content-section">
   <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{$page->getLocalTranslation('title')}}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">

        <div class="w-100 mb-6" data-aos="fade-up" data-aos-duration="2000">
            <p class="lead">{{getLanguageKeyLocalTranslation('achievements_page_description')}}</p>
        </div>

        <div class="row pt-6" data-aos="fade-up" data-aos-duration="2000">
            <div class="col-lg-8">
                <!-- Achievements by Year -->
                @if($achievementsByYear->count() > 0)
                    @foreach($achievementsByYear as $year => $yearAchievements)
                        <div class="mb-12" data-aos="fade-up" data-aos-duration="1000">
                            <!-- Year Bubble -->
                            <div class="mb-6">
                                <span class="badge bg-primary text-white rounded-pill px-6 py-3 fs-18 fw-bold">
                                    {{ $year }}
                                </span>
                            </div>
                            
                            <!-- Achievements Slider for this Year -->
                            <div class="swiper-container" 
                                data-margin="20" 
                                data-autoplay="true" 
                                data-autoplaytime="5000" 
                                data-dots="false" 
                                data-nav="true" 
                                data-items-xl="3" 
                                data-items-md="2" 
                                data-items-xs="1"
                                data-loop="true">
                                
                                <div class="swiper">
                                    <div class="swiper-wrapper">
                                        @foreach($yearAchievements as $achievement)
                                            <div class="swiper-slide">
                                                <a href="{{ $achievement->url }}" class="shadow-lg lift h-100 d-block p-5 d-flex flex-row">
                                                    <div>
                                                        @if($achievement->thumbnailUrl)
                                                            <img src="{{ $achievement->thumbnailUrl }}" alt="{{ $achievement->getLocalTranslation('title') }}" class="avatar w-11 h-11 rounded-circle object-cover me-4" style="object-fit: cover;">
                                                        @else
                                                            <span class="avatar bg-primary text-white w-11 h-11 fs-20 me-4">
                                                                {{ strtoupper(substr($achievement->getLocalTranslation('title'), 0, 2)) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        @if($achievement->category)
                                                            <span class="badge bg-pale-blue text-blue rounded py-1 mb-2">
                                                                {{ $achievement->category->getLocalTranslation('title') }}
                                                            </span>
                                                        @endif
                                                        <h4 class="mb-1">{{ $achievement->getLocalTranslation('title') }}</h4>
                                                        @if($achievement->getLocalTranslation('done_by'))
                                                            <p class="mb-0 text-body">
                                                                <i class="uil uil-user me-1"></i>
                                                                {{ $achievement->getLocalTranslation('done_by') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </a>
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
                    @endforeach
                @else
                    <div class="text-center py-10 card shadow-sm border-0">
                        <div class="card-body">
                            <div class="icon-shape bg-soft-primary rounded-circle mb-4">
                                <i class="uil uil-award fs-30 text-primary"></i>
                            </div>
                            <h4>{{getLanguageKeyLocalTranslation('achievements_page_no_results_title')}}</h4>
                            <p>{{getLanguageKeyLocalTranslation('achievements_page_no_results_description')}}</p>
                        </div>
                    </div>
                @endif
            </div>
            <!-- /column -->

            <aside class="col-lg-4 sidebar">
                <div class="widget">
                    <h4 class="widget-title mb-3">
                        {{getLanguageKeyLocalTranslation('sidebar_achievements_search_title')}}
                    </h4>
                    <form class="search-form" action="{{route('achievements')}}">
                        <div class="mb-0">
                            <input name="search" value="{{request()->get('search')}}" type="text" class="form-control" placeholder="{{getLanguageKeyLocalTranslation('sidebar_achievements_search_placeholder')}}" onchange="this.form.submit()">
                        </div>
                    </form>
                    <!-- /.search-form -->
                </div>
                <!-- /.widget -->

                <div class="widget">
                    <h4 class="widget-title mb-3">
                        {{getLanguageKeyLocalTranslation('sidebar_achievements_categories_title')}}
                    </h4>
                    <form action="{{route('achievements')}}" method="get">
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{request()->get('search')}}">
                        @endif
                        @if(request('year'))
                            <input type="hidden" name="year" value="{{request()->get('year')}}">
                        @endif
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">{{getLanguageKeyLocalTranslation('sidebar_achievements_all_categories')}}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                                    {{ $cat->getLocalTranslation('title') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <!-- /.widget -->

                <div class="widget">
                    <h4 class="widget-title mb-3">
                        {{getLanguageKeyLocalTranslation('sidebar_achievements_years_title')}}
                    </h4>
                    <form action="{{route('achievements')}}" method="get">
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{request()->get('search')}}">
                        @endif
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{request()->get('category')}}">
                        @endif
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            <option value="">{{getLanguageKeyLocalTranslation('sidebar_achievements_all_years')}}</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <!-- /.widget -->

                @if(request('category') || request('year') || request('search'))
                    <div class="widget">
                        <a href="{{ route('achievements') }}" class="btn btn-outline-primary w-100 rounded-pill">
                            {{getLanguageKeyLocalTranslation('sidebar_achievements_clear_filters')}}
                        </a>
                    </div>
                @endif
            </aside>
            <!-- /column .sidebar -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>

@endsection
@section('script')
@endsection
