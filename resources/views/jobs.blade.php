@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('jobs'))
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
    <div class="container py-12 py-md-5">
        <h2 class="display-5 mb-3">
            {{$page->getLocalTranslation('title')}}
        </h2>
        <p class="lead fs-md">
            {{$page->getLocalTranslation('description')}}
        </p>
        <hr class="mt-2 mb-4">
        <div class="w-100 page-content">
            <x-markdown>
                {{ $page->getLocalTranslation('content') }}
            </x-markdown>
        </div>

        <hr class="mt-2 mb-0">
    </div>
    <!-- /.container -->
</section>

<!-- Job Listings Section -->
<section class="wrapper bg-light">
    <div class="container pb-12">
        <div class="row justify-content-center">
            <!-- Search Form -->
            <form action="{{route('jobs')}}" method="GET" class="row g-3">
                <div class="col-md-9">
                    <div class="form-floating">
                        <input name="search" value="{{request()->get('search')}}" id="search-form" type="text" class="form-control" placeholder="Search jobs...">
                        <label for="search-form">
                        {{getLanguageKeyLocalTranslation('jobs_search_placeholder')}}
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 h-100">
                    <i class="uil uil-search me-1"></i>{{getLanguageKeyLocalTranslation('jobs_search_button')}}
                    </button>
                </div>
            </form>
            <!-- Job Listings -->
            <div class="row mt-8">
                @foreach ($jobs as $job)
                <div class="card col-md-6 col-lg-4 mb-4 hover-lift">
                    <div class="card-body">
                        <h2 class="h3 mt-1 mb-3">
                            <a href="{{route('job', ['slug' => $job->slug])}}" class="link-dark text-decoration-none">
                                {{$job->getLocalTranslation('title')}}
                            </a>
                        </h2>

                        <div class="meta mb-2 d-flex flex-wrap gap-2">
                            @if($job->employment_type == 'full_time')
                                <span class="badge bg-success">{{getLanguageKeyLocalTranslation('jobs_full_time')}}</span>
                            @elseif($job->employment_type == 'part_time')
                                <span class="badge bg-warning">{{getLanguageKeyLocalTranslation('jobs_part_time')}}</span>
                            @else
                                <span class="badge bg-info">{{getLanguageKeyLocalTranslation('jobs_internship')}}</span>
                            @endif

                            @if($job->is_remote)
                            <span class="badge bg-primary">
                                <i class="uil uil-laptop me-1"></i>{{getLanguageKeyLocalTranslation('jobs_remote')}}
                            </span>
                            @endif
                        </div>

                        <p class="mb-4 text-muted truncate-3-lines">
                            {{$job->getLocalTranslation('description')}}
                        </p>

                        <div class="mt-2">
                            @if($job->getLocalTranslation('required_skills'))
                                @php
                                    $skills = explode(',', $job->getLocalTranslation('required_skills'));
                                    $displaySkills = array_slice(array_filter(array_map('trim', $skills)), 0, 3);
                                @endphp
                                @if(count($displaySkills) > 0)
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @foreach($displaySkills as $skill)
                                        <span class="badge bg-light text-dark">{{ $skill }}</span>
                                    @endforeach
                                    @if(count($skills) > 3)
                                        <span class="badge bg-light text-muted">+{{ count($skills) - 3 }}</span>
                                    @endif
                                </div>
                                @endif
                            @endif
                        </div>

                        <div class="mb-2">
                            @if($job->application_deadline)
                            <small class="text-muted d-block">
                            <i class="uil uil-calendar-alt me-1"></i>
                            {{getLanguageKeyLocalTranslation('jobs_deadline')}}: {{ $job->application_deadline }}
                            </small>
                            @endif
                        </div>
                        <a href="{{route('job', ['slug' => $job->slug])}}" class="btn btn-sm btn-primary">
                        {{getLanguageKeyLocalTranslation('jobs_view_details')}}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            <!-- Pagination -->
            <nav class="d-flex" aria-label="pagination">
                <ul class="pagination">
                    <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
                    <a class="page-link" href="{{ route('jobs', array_merge(['page' => $pagination["prevPage"]], request()->only('search'))) }}" aria-label="Previous">
                    <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
                    </a>
                    </li>
                    @foreach($pagination["pages"] as $page)
                    <li class="page-item">
                        <a class="page-link {{($page == $pagination['currentPage']) ? 'active' : ''}}" href="{{ route('jobs', array_merge(['page' => $page], request()->only('search'))) }}">
                        {{$page}}
                        </a>
                    </li>
                    @endforeach
                    <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                    <a class="page-link" href="{{ route('jobs', array_merge(['page' => $pagination["nextPage"]], request()->only('search'))) }}" aria-label="Next">
                    <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
                    </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</section>
@endsection
@section('script')
@endsection