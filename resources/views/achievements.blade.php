@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('achievements'))
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
                @if($achievementsByYear->count() > 0)
                    <div class="timeline-container">
                        @foreach($achievementsByYear as $yearIndex => $yearAchievements)
                            @php
                                $year = $yearIndex;
                                $isFirstYear = $loop->first;
                            @endphp
                            
                            <div class="timeline-item">
                                <!-- Left Side (Year) -->
                                <div class="timeline-date">
                                    <span class="badge bg-primary text-white px-4 py-2 fs-14 fw-bold" style="border-radius: 6px;">{{ $year }}</span>
                                </div>
                                
                                <!-- Center Line & Node -->
                                <div class="timeline-line">
                                    <div class="timeline-dot {{ $isFirstYear ? '' : 'inactive' }}"></div>
                                    <div class="timeline-vertical-line"></div>
                                </div>
                                
                                <!-- Right Side (Slider) -->
                                <div class="timeline-content">
                                    <!-- Mobile Year -->
                                    <div class="timeline-content-mobile-date">
                                        <span class="badge bg-primary text-white px-4 py-2 fs-14 fw-bold" style="border-radius: 6px;">{{ $year }}</span>
                                    </div>
                                    
                                    <!-- Achievements Slider for this Year -->
                                    <div class="swiper-container px-2" 
                                        data-margin="20" 
                                        data-autoplay="true" 
                                        data-autoplaytime="5000" 
                                        data-dots="true" 
                                        data-nav="false" 
                                        data-items-xl="2" 
                                        data-items-lg="1" 
                                        data-items-md="1" 
                                        data-items-xs="1"
                                        data-loop="true">
                                        
                                        <div class="swiper">
                                            <div class="swiper-wrapper pb-2 px-2">
                                                @foreach($yearAchievements as $achievement)
                                                    <div class="swiper-slide">
                                                        <div class="card">
                                                            @if($achievement->thumbnailUrl)
                                                                <figure class="hover-scale">
                                                                    <a href="{{ $achievement->url }}">
                                                                        <img src="{{ $achievement->thumbnailUrl }}" alt="{{ $achievement->getLocalTranslation('title') }}" />
                                                                    </a>
                                                                </figure>
                                                            @endif

                                                            <div class="card-body">
                                                                @if($achievement->category)
                                                                    <span class="badge bg-primary rounded py-1 mb-2">
                                                                        {{ $achievement->category->getLocalTranslation('title') }}
                                                                    </span>
                                                                @endif
                                                                <h2>
                                                                    <a href="{{ $achievement->url }}">
                                                                        {{ $achievement->getLocalTranslation('title') }}
                                                                    </a>
                                                                </h2>

                                                                <p class="mb-0">
                                                                    {{ $achievement->getLocalTranslation('description') }}
                                                                </p>
                                                            </div>
                                                            <!--/.card-body -->
                                                            
                                                            <div class="card-footer">
                                                                <ul class="post-meta d-flex mb-0">
                                                                    <li class="post-date">
                                                                        <i class="uil uil-calendar-alt"></i>
                                                                        <span>{{ \Carbon\Carbon::parse($achievement->achievement_date)->format('Y-m-d') }}</span>
                                                                    </li>
                                                                    @if($achievement->getLocalTranslation('done_by'))
                                                                        <li class="ms-3">
                                                                            <i class="uil uil-user"></i>
                                                                            <span>{{ $achievement->getLocalTranslation('done_by') }}</span>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                                <!-- /.post-meta -->
                                                            </div>
                                                            <!-- /.card-footer -->
                                                        </div>
                                                        <!-- /.card -->
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
                            </div>
                        @endforeach
                        
                        <!-- Timeline End Indicator -->
                        <div class="timeline-item" style="padding-bottom: 0;">
                            <div class="timeline-date"></div>
                            <div class="timeline-line">
                                <div class="timeline-dot inactive" style="width: 0.5rem; height: 0.5rem; margin-top: 0;"></div>
                                <div class="timeline-vertical-line" style="display: none;"></div>
                            </div>
                            <div class="timeline-content"></div>
                        </div>
                    </div>
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
                            <input name="search" value="{{request()->get('search')}}" id="search-form" type="text" class="form-control">
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
