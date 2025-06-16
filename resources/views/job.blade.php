@extends('layouts.master')
@section('title', $job->getLocalTranslation('title'))
@section('description', $job->getLocalTranslation('description'))
@section('canonical', route('job', ['slug' => $job->slug]))
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$job->getLocalTranslation('title')}}
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
                <a class="text-uppercase" href="{{route('jobs')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_jobs_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$job->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>


<section class="wrapper bg-light">
    <div class="container py-12 py-md-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="h1 mb-3">{{getLanguageKeyLocalTranslation('job_description_title')}}</h2>
                        
                        @if($job->getLocalTranslation('description'))
                        <div class="mb-4">
                            <p>{{$job->getLocalTranslation('description')}}</p>
                        </div>
                        @endif

                        @if($job->getLocalTranslation('content'))
                        <div class="mb-6">
                            <x-markdown>
                                {{ $job->getLocalTranslation('content') }}
                            </x-markdown>
                        </div>
                        @endif

                        <!-- Application Actions -->
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            @if($job->application_deadline && $job->application_deadline < now())
                                <div class="alert alert-warning mb-0">
                                    <i class="uil uil-exclamation-triangle me-2"></i>
                                    {{getLanguageKeyLocalTranslation('job_application_expired')}}
                                </div>
                            @else
                                <a href="" class="btn btn-primary rounded-pill">
                                    <i class="uil uil-envelope me-2"></i>{{getLanguageKeyLocalTranslation('job_apply_now')}}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-lg mb-4">
                    <div class="card-body">
                        <h3 class="h3 mb-4">{{getLanguageKeyLocalTranslation('job_details_title')}}</h3>
                        <ul class="list-unstyled mb-0">
                            @if($job->required_years_of_experience)
                            <li class="mb-3">
                                <i class="uil uil-clock text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_experience_required')}}:</strong>
                                <span class="ms-2">{{ $job->required_years_of_experience }}+ {{getLanguageKeyLocalTranslation('job_years')}}</span>
                            </li>
                            @endif

                            @if($job->getLocalTranslation('required_skills'))
                            <li class="mb-3">
                                <i class="uil uil-star text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_required_skills_title')}}:</strong>
                                <div class="mt-2">
                                    @php
                                        $skills = explode(',', $job->getLocalTranslation('required_skills'));
                                    @endphp
                                    @foreach(array_filter(array_map('trim', $skills)) as $skill)
                                        <span class="badge bg-light text-dark me-1 mb-1">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </li>
                            @endif

                            <li class="mb-3">
                                <i class="uil uil-briefcase text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_employment_type')}}:</strong>
                                <span class="ms-2">
                                    @if($job->employment_type == 'full_time')
                                        {{getLanguageKeyLocalTranslation('jobs_full_time')}}
                                    @elseif($job->employment_type == 'part_time')
                                        {{getLanguageKeyLocalTranslation('jobs_part_time')}}
                                    @else
                                        {{getLanguageKeyLocalTranslation('jobs_internship')}}
                                    @endif
                                </span>
                            </li>

                            <li class="mb-3">
                                <i class="uil uil-{{ $job->is_remote ? 'laptop' : 'building' }} text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_work_type')}}:</strong>
                                <span class="ms-2">
                                    @if($job->is_remote)
                                        {{getLanguageKeyLocalTranslation('jobs_remote')}}
                                    @else
                                        {{getLanguageKeyLocalTranslation('jobs_onsite')}}
                                    @endif
                                </span>
                            </li>

                            <li class="mb-3">
                                <i class="uil uil-users-alt text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_positions_available')}}:</strong>
                                <span class="ms-2">{{ $job->number_of_positions }}</span>
                            </li>

                            @if($job->application_deadline)
                            <li class="mb-3">
                                <i class="uil uil-calendar-alt text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_application_deadline')}}:</strong>
                                <span class="ms-2">{{ $job->application_deadline }}</span>
                                @if($job->application_deadline < now()->addDays(7))
                                    <span class="badge bg-warning text-dark ms-1">
                                        {{getLanguageKeyLocalTranslation('jobs_closing_soon')}}
                                    </span>
                                @endif
                            </li>
                            @endif

                            <li class="mb-0">
                                <i class="uil uil-calendar-plus text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_posted_date')}}:</strong>
                                <span class="ms-2">{{ $job->created_at }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('script')
@endsection