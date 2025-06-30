@extends('admin.layouts.master')
@section('title') {{ $jobPosting->getLocalTranslation('title') }} - Job Applications @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Job Postings @endslot
@slot('li_2') {{ $jobPosting->getLocalTranslation('title') }} @endslot
@slot('title') Job Applications @endslot
@endcomponent

<div class="row">
    <div class="col">
        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Job Applications for: {{ $jobPosting->name }}</h4>
                            <p class="text-muted mb-0">Total Applications: {{ $applications->total() }}</p>
                        </div>
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.job-applications.export', $jobPosting->id) }}" class="btn btn-soft-success">
                                            <i class="ri-download-line align-middle me-1"></i>
                                            Export CSV
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('admin.job-postings.index') }}" class="btn btn-soft-primary">
                                            <i class="ri-arrow-left-line align-middle me-1"></i>
                                            Back to Jobs
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="card-title mb-0">Applications</h4>
                                <div class="d-flex gap-2">
                                    <!-- AI Score Filter -->
                                    <select class="form-select" style="width: auto;" onchange="applyFilter('ai_score_min', this.value)">
                                        <option value="">All Scores</option>
                                        <option value="8" {{ request('ai_score_min') == '8' ? 'selected' : '' }}>8+ Score</option>
                                        <option value="7" {{ request('ai_score_min') == '7' ? 'selected' : '' }}>7+ Score</option>
                                        <option value="6" {{ request('ai_score_min') == '6' ? 'selected' : '' }}>6+ Score</option>
                                        <option value="5" {{ request('ai_score_min') == '5' ? 'selected' : '' }}>5+ Score</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filters -->
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-3">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{ route('admin.job-applications.index', $jobPosting->id) }}">
                                        @if(request('ai_score_min'))
                                            <input type="hidden" name="ai_score_min" value="{{ request('ai_score_min') }}">
                                        @endif
                                        
                                        <div class="d-flex align-items-center">
                                            <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50 me-2">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.job-applications.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%">#</th>
                                            <th scope="col">Applicant</th>
                                            <th scope="col">Contact</th>
                                            <th scope="col">AI Score</th>
                                            <th scope="col">Applied</th>
                                            <th scope="col" width="100px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($applications as $application)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="{{ route('admin.job-applications.show', $application->id) }}" class="fw-semibold">
                                                    #{{ $application->id }}
                                                </a>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">{{ $application->first_name }} {{ $application->last_name }}</h6>
                                                        <p class="text-muted mb-0">{{ $application->nationality }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div>
                                                    <span class="text-primary">{{ $application->email }}</span><br>
                                                    <small class="text-muted">{{ $application->phone }}</small>
                                                </div>
                                            </td>

                                            <td>
                                                @if($application->ai_score)
                                                    @php
                                                        $scoreClass = $application->ai_score >= 8 ? 'success' : 
                                                                     ($application->ai_score >= 6 ? 'warning' : 'danger');
                                                    @endphp
                                                    <span class="badge bg-{{ $scoreClass }}">{{ $application->ai_score }}/10</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending score</span>
                                                @endif
                                            </td>

                                            <td>
                                                <span class="text-muted">{{ $application->created_at->format('M d, Y H:i') }}</span><br>
                                            </td>

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" role="button" id="dropdownMenuLink{{ $application->id }}" 
                                                       data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $application->id }}">
                                                        @haspermission('admin.job-applications.show')
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.job-applications.show', $application->id) }}">
                                                                    <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Details
                                                                </a>
                                                            </li>
                                                        @endhaspermission

                                                        @if($application->cv)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ $application->cv->url }}" target="_blank">
                                                                    <i class="ri-download-line align-bottom me-2 text-muted"></i> Download CV
                                                                </a>
                                                            </li>
                                                        @endif

                                                        
                                                        <li><hr class="dropdown-divider"></li>

                                                        @haspermission('admin.job-applications.destroy')
                                                            <li>
                                                                <form action="{{ route('admin.job-applications.destroy', $application->id) }}" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="dropdown-item text-danger" type="submit">
                                                                        <i class="ri-delete-bin-fill align-bottom me-2"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endhaspermission
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endhaspermission
                        </div>

                        <div class="card-footer">
                            <div class="align-items-center justify-content-between d-flex">
                                <div class="flex-shrink-0">
                                    <div class="text-muted">
                                        Showing from {{$pagination['from']}} to {{$pagination['to']}} of {{$pagination['total']}} results
                                    </div>
                                </div>
                                <ul class="pagination pagination-separated pagination-sm mb-0">
                                    <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('admin.job-applications.index', array_merge(['page' => $pagination["prevPage"], 'jobPosting' => $jobPosting], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">←</a>
                                    </li>
                                    
                                    @foreach($pagination["pages"] as $page)
                                    <li class="page-item {{($page == $pagination['currentPage']) ? 'active' : ''}}">
                                        <a href="{{ route('admin.job-applications.index', array_merge(['page' => $page, 'jobPosting' => $jobPosting], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">
                                            {{$page}}
                                        </a>
                                    </li>
                                    @endforeach

                                    <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('admin.job-applications.index', array_merge(['page' => $pagination["nextPage"], 'jobPosting' => $jobPosting], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">→</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
<script>
function applyFilter(filterName, filterValue) {
    const url = new URL(window.location.href);
    if (filterValue) {
        url.searchParams.set(filterName, filterValue);
    } else {
        url.searchParams.delete(filterName);
    }
    window.location.href = url.toString();
}
</script>
@endsection
