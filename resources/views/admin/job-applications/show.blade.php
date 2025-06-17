@extends('admin.layouts.master')
@section('title') Job Application #{{ $jobApplication->id }} @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Job Applications @endslot
@slot('li_2') {{ $jobApplication->jobPosting->getLocalTranslation('title') }} @endslot
@slot('title') Application #{{ $jobApplication->id }} @endslot
@endcomponent

<div class="row">
    <div class="col">
        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Application Details</h4>
                            <p class="text-muted mb-0">{{ $jobApplication->first_name }} {{ $jobApplication->last_name }} - {{ $jobApplication->jobPosting->getLocalTranslation('title') }}</p>
                        </div>
                        <div class="mt-3 mt-lg-0">
                            <div class="d-flex gap-2">
                                @if($jobApplication->ai_score)
                                    @php
                                        $scoreClass = $jobApplication->ai_score >= 8 ? 'success' : 
                                                     ($jobApplication->ai_score >= 6 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $scoreClass }} fs-12">AI Score: {{ $jobApplication->ai_score }}/10</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Personal Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h4 class="fs-15">Full Name</h4>
                                        <p class="text-muted mb-0">{{ $jobApplication->first_name }} {{ $jobApplication->last_name }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h4 class="fs-15">Email Address</h4>
                                        <p class="text-muted mb-0">
                                            <a href="mailto:{{ $jobApplication->email }}" class="text-primary">{{ $jobApplication->email }}</a>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h4 class="fs-15">Phone Number</h4>
                                        <p class="text-muted mb-0">
                                            <a href="tel:{{ $jobApplication->phone }}" class="text-primary">{{ $jobApplication->phone }}</a>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h4 class="fs-15">Nationality</h4>
                                        <p class="text-muted mb-0">{{ $jobApplication->nationality }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h4 class="fs-15">Address</h4>
                                        <p class="text-muted mb-0">{{ $jobApplication->address }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h4 class="fs-15">Application Date</h4>
                                        <p class="text-muted mb-0">{{ $jobApplication->created_at->format('M d, Y \a\t H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Education Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Education Background</h4>
                        </div>
                        <div class="card-body">
                            @if($jobApplication->education->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Institution</th>
                                                <th scope="col">Degree</th>
                                                <th scope="col">Field of Study</th>
                                                <th scope="col">Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jobApplication->education as $education)
                                            <tr>
                                                <td>
                                                    <h6 class="mb-1">{{ $education->institution }}</h6>
                                                </td>
                                                <td>{{ $education->degree }}</td>
                                                <td>{{ $education->field_of_study }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $education->start_year }} - {{ $education->end_year }}</span>
                                                </td>
                                            </tr>
                                            @if($education->description)
                                            <tr>
                                                <td colspan="4">
                                                    <small class="text-muted">{{ $education->description }}</small>
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <p class="text-muted">No education information provided</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Work Experience Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Work Experience</h4>
                        </div>
                        <div class="card-body">
                            @if($jobApplication->experiences->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Position</th>
                                                <th scope="col">Company</th>
                                                <th scope="col">Duration</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jobApplication->experiences as $experience)
                                            <tr>
                                                <td>
                                                    <h6 class="mb-1">{{ $experience->job_title }}</h6>
                                                </td>
                                                <td>{{ $experience->company_name }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        {{ $experience->start_year }} - {{ $experience->is_current ? 'Present' : $experience->end_year }}
                                                        @php
                                                            $endYear = $experience->is_current ? date('Y') : $experience->end_year;
                                                            $years = $endYear - $experience->start_year;
                                                        @endphp
                                                        ({{ $years }} {{ $years == 1 ? 'year' : 'years' }})
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($experience->is_current)
                                                        <span class="badge bg-success">Current</span>
                                                    @else
                                                        <span class="badge bg-secondary">Previous</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($experience->description)
                                            <tr>
                                                <td colspan="4">
                                                    <small class="text-muted">{{ $experience->description }}</small>
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <p class="text-muted">No work experience provided</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Languages & Skills Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Languages & Skills</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Languages Section -->
                                <div class="col-md-6">
                                    <h5 class="fs-15 mb-3">Languages</h5>
                                    @if($jobApplication->languages->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    @foreach($jobApplication->languages as $language)
                                                    <tr>
                                                        <td>{{ $language->name }}</td>
                                                        <td>
                                                            @switch($language->proficiency)
                                                                @case('basic')
                                                                    <span class="badge bg-light text-dark">Basic</span>
                                                                    @break
                                                                @case('intermediate')
                                                                    <span class="badge bg-warning">Intermediate</span>
                                                                    @break
                                                                @case('advanced')
                                                                    <span class="badge bg-primary">Advanced</span>
                                                                    @break
                                                                @case('native')
                                                                    <span class="badge bg-success">Native</span>
                                                                    @break
                                                            @endswitch
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No languages specified</p>
                                    @endif
                                </div>

                                <!-- Skills Section -->
                                <div class="col-md-6">
                                    <h5 class="fs-15 mb-3">Skills</h5>
                                    @if($jobApplication->skills_array && count($jobApplication->skills_array) > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($jobApplication->skills_array as $skill)
                                                <span class="badge bg-primary">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No skills specified</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents & Additional Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Documents & Additional Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="fs-15 mb-3">CV/Resume</h5>
                                    @if($jobApplication->cv)
                                        <div class="d-flex align-items-center p-3 border rounded">
                                            <div class="flex-shrink-0">
                                                <i class="ri-file-pdf-line text-danger" style="font-size: 2rem;"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">CV/Resume</h6>
                                                <p class="text-muted mb-0">Uploaded: {{ $jobApplication->created_at->format('M d, Y') }}</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <a href="{{ $jobApplication->cv->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="ri-download-line me-1"></i>Download
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted">No CV uploaded</p>
                                    @endif
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="fs-15 mb-3">Application Analytics</h5>
                                    <div class="border rounded p-3">
                                        @if($jobApplication->ai_score)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>AI Score:</span>
                                                @php
                                                    $scoreClass = $jobApplication->ai_score >= 8 ? 'success' : 
                                                                 ($jobApplication->ai_score >= 6 ? 'warning' : 'danger');
                                                @endphp
                                                <span class="badge bg-{{ $scoreClass }}">{{ $jobApplication->ai_score }}/10</span>
                                            </div>
                                            @if($jobApplication->ai_scored_at)
                                                <small class="text-muted">Scored: {{ $jobApplication->ai_scored_at->format('M d, Y H:i') }}</small>
                                            @endif
                                        @else
                                            <p class="text-muted mb-0">AI scoring pending</p>
                                        @endif
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                Applied: {{ $jobApplication->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-end mb-3">
                        <a href="{{ route('admin.job-applications.index', $jobApplication->jobPosting->id) }}" class="btn btn-primary w-sm">
                            <i class="ri-arrow-left-line me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
