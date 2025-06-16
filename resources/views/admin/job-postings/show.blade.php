@extends('admin.layouts.master')
@section('title') Job Postings @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Job Postings @endslot
@slot('title') Show Job Posting @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good Morning, {{Auth::user()->fullname}}</h4>
                        </div>
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Job Posting Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                    <div class="mb-3">
                                        <h4 class="fs-15">Job Name</h4>
                                    {{$jobPosting->name}}
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Job Slug</h4>
                                    <span class="badge bg-primary">{{$jobPosting->slug}}</span>
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Employment Type</h4>
                                    @if($jobPosting->employment_type == 'full_time')
                                        <span class="badge bg-success">Full Time</span>
                                    @elseif($jobPosting->employment_type == 'part_time')
                                        <span class="badge bg-warning">Part Time</span>
                                    @else
                                        <span class="badge bg-info">Internship</span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Remote Work</h4>
                                    @if($jobPosting->is_remote)
                                        <span class="badge bg-success">
                                            <i class="ri-check-line me-1"></i>Remote Available
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="ri-close-line me-1"></i>On-site Only
                                        </span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Number of Positions</h4>
                                    <span class="fw-semibold">{{$jobPosting->number_of_positions}} positions</span>
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Job Status</h4>
                                    @if($jobPosting->status == 'draft')
                                        <span class="badge bg-secondary">Draft</span>   
                                    @elseif($jobPosting->status == 'hidden')   
                                        <span class="badge bg-warning">Hidden</span>                                                 
                                    @else
                                        <span class="badge bg-success">Published</span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Required Years of Experience</h4>
                                    <span class="fw-semibold">{{$jobPosting->required_years_of_experience}}</span>
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Application Deadline</h4>
                                    <span class="fw-semibold">{{ $jobPosting->application_deadline }}</span>
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Created At</h4>
                                    {{ $jobPosting->created_at }}
                                </div>

                                <div class="mb-3">
                                    <h4 class="fs-15">Last Updated</h4>
                                    {{ $jobPosting->updated_at }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-success mb-3" role="tablist">
                                @foreach ($languages as $key => $language)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{$key == 0 ? "active" : ""}}" data-bs-toggle="tab" href="#{{$language->code}}" role="tab" aria-selected="true">
                                            <i class="ri-translate align-middle me-1"></i> {{$language->name}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content text-muted">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <div class="mb-4">
                                            <h4 class="fs-15">Job {{$language->name}} Title</h4>
                                            @if($jobPosting->getTranslation('title', $language->code))
                                                <p class="mb-0">{{$jobPosting->getTranslation('title', $language->code)}}</p>
                                            @else
                                                <p class="text-muted mb-0"><em>No translation available</em></p>
                                            @endif
                                        </div>

                                        <div class="mb-4">
                                            <h4 class="fs-15">Job {{$language->name}} Description</h4>
                                            @if($jobPosting->getTranslation('description', $language->code))
                                                <p class="mb-0">{{$jobPosting->getTranslation('description', $language->code)}}</p>
                                            @else
                                                <p class="text-muted mb-0"><em>No translation available</em></p>
                                            @endif
                                        </div>

                                        @if($jobPosting->getTranslation('required_skills', $language->code))
                                        <div class="mb-4">
                                            <h4 class="fs-15">Required Skills ({{$language->name}})</h4>
                                            @php
                                                $skills = explode(',', $jobPosting->getTranslation('required_skills', $language->code));
                                            @endphp
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($skills as $skill)
                                                    @if(trim($skill))
                                                        <span class="badge bg-light text-dark border">{{ trim($skill) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif

                                        <div class="mb-4">
                                            <h4 class="fs-15">Job {{$language->name}} Content</h4>
                                            @if($jobPosting->getTranslation('content', $language->code))
                                                <div class="border rounded p-3" style="background: #f8f9fa;">
                                                    <x-markdown>
                                                        {{ $jobPosting->getTranslation('content', $language->code) }}
                                                    </x-markdown>
                                                </div>
                                            @else
                                                <p class="text-muted mb-0"><em>No translation available</em></p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.job-postings.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.job-postings.update')
                            <a href="{{ route('admin.job-postings.edit', $jobPosting->id) }}" class="btn btn-success w-sm">Edit</a>
                        @endhaspermission
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->    

        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
