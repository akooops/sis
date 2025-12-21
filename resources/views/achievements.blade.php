@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('achievements'))

@section('css')
<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
        left: 31px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 50px;
        padding-left: 80px;
    }
    .timeline-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .timeline-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .timeline-item:hover .timeline-icon {
        border-color: #3f78e0;
        transform: scale(1.1);
    }
    .timeline-date {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
        font-weight: 600;
        text-transform: uppercase;
        display: block;
    }
    .timeline-content {
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 1px solid #f1f3f5;
    }
    .timeline-item:hover .timeline-content {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .timeline-title {
        margin-bottom: 12px;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .timeline-category {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        background: rgba(63, 120, 224, 0.1);
        color: #3f78e0;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sidebar .widget {
        margin-bottom: 40px;
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .widget-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    .filter-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .filter-item {
        margin-bottom: 10px;
    }
    .filter-item:last-child {
        margin-bottom: 0;
    }
    .filter-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-radius: 8px;
        color: #495057;
        text-decoration: none;
        transition: all 0.2s ease;
        background: #f8f9fa;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .filter-link:hover, .filter-link.active {
        background: #3f78e0;
        color: #fff !important;
    }
    .filter-count {
        font-size: 0.75rem;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 10px;
        color: #6c757d;
    }
    .filter-link:hover .filter-count, .filter-link.active .filter-count {
        background: rgba(255,255,255,0.2);
        color: #fff;
    }
</style>
@endsection

@section('content')
<section class="wrapper banners-section">
    <div class="swiper-container">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $page->thumbnailUrl }}')"></div>
                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">
                            <div class="col-12 col-lg-8">
                                <h1 class="mb-0 text-white animate__animated animate__slideInDown animate__delay-1s">
                                    {{$page->getLocalTranslation('title')}}
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
    </div>
</section>

<section class="wrapper">
    <div class="container pt-6 pb-12" data-aos="fade-up" data-aos-duration="1000">
        <div class="row gx-lg-12">
            <div class="col-lg-8">
                <div class="timeline">
                    @forelse($achievements as $achievement)
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <img src="{{ $achievement->thumbnailUrl }}" alt="">
                            </div>
                            <div class="timeline-content">
                                <span class="timeline-date">
                                    <i class="uil uil-calendar-alt me-1"></i>
                                    {{ \Carbon\Carbon::parse($achievement->achievement_date)->format('F Y') }}
                                </span>
                                <div class="timeline-category">
                                    {{ $achievement->category->getLocalTranslation('name') }}
                                </div>
                                <h3 class="timeline-title">
                                    <a href="{{ $achievement->url }}" class="link-dark">{{ $achievement->getLocalTranslation('title') }}</a>
                                </h3>
                                @if($achievement->getLocalTranslation('done_by'))
                                    <p class="text-muted small mb-3">
                                        <i class="uil uil-user me-1 text-primary"></i> <strong>{{ $achievement->getLocalTranslation('done_by') }}</strong>
                                    </p>
                                @endif
                                <p class="mb-0 text-secondary">
                                    {{ $achievement->getLocalTranslation('description') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 card shadow-sm border-0">
                             <div class="card-body">
                                <div class="icon-shape bg-soft-primary rounded-circle mb-4">
                                     <i class="uil uil-award fs-30 text-primary"></i>
                                </div>
                                <h4>No achievements found</h4>
                                <p>Try adjusting your search filters or check back later.</p>
                             </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <aside class="col-lg-4 sidebar mt-10 mt-lg-0">
                <div class="widget">
                    <h4 class="widget-title">Categories</h4>
                    <ul class="filter-list">
                        <li class="filter-item">
                            <a href="{{ route('achievements', request()->only('year')) }}" class="filter-link {{ !request('category') ? 'active' : '' }}">
                                All Categories
                            </a>
                        </li>
                        @foreach($categories as $cat)
                            <li class="filter-item">
                                <a href="{{ route('achievements', array_merge(request()->only('year'), ['category' => $cat->slug])) }}" 
                                   class="filter-link {{ request('category') == $cat->slug ? 'active' : '' }}">
                                    {{ $cat->getLocalTranslation('name') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="widget">
                    <h4 class="widget-title">Filter by Year</h4>
                    <ul class="filter-list">
                        <li class="filter-item">
                            <a href="{{ route('achievements', request()->only('category')) }}" class="filter-link {{ !request('year') ? 'active' : '' }}">
                                All Years
                            </a>
                        </li>
                        @foreach($years as $year)
                            <li class="filter-item">
                                <a href="{{ route('achievements', array_merge(request()->only('category'), ['year' => $year])) }}" 
                                   class="filter-link {{ request('year') == $year ? 'active' : '' }}">
                                    {{ $year }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if(request('category') || request('year'))
                    <div class="mt-5">
                        <a href="{{ route('achievements') }}" class="btn btn-outline-primary w-100 rounded-pill">
                            Clear All Filters
                        </a>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
