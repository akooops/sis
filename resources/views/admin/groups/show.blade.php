@extends('admin.layouts.master')
@section('title') Groups @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Groups @endslot
@slot('title') Show Group @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning , {{Auth::user()->fullname}}</h4>
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
                            <h4 class="card-title mb-0">Group Inforamtion</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Group Name</h4>
                                {{$group->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Group Subscribers Count</h4>
                                <span class="badge bg-success">{{count($group->Subscribers)}}</span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Created By</h4>
                                @if(is_null($group->user))
                                    <span class="badge bg-primary">Deleted User</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $group->user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $group->user->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Updated By</h4>
                                @if(is_null($group->updatedBy))
                                    <span class="badge bg-primary">Deleted User</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $group->updatedBy->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $group->updatedBy->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Subscribers Emails</h4>
                                <a class="btn btn-success" href="{{ route('groups.export', $group->id) }}">
                                    <i class="ri-download-cloud-line align-bottom me-1"></i> Export CSV
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->

                <div class="text-end mb-3">
                    <a href="{{ route('groups.index') }}" class="btn btn-primary w-sm">Back</a>
                    <a href="{{ route('groups.edit', $group->id) }}" class="btn btn-success w-sm">Edit</a>
                </div>
            </div>
            <!-- end row -->
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/admin/js/app.min.js') }}"></script>
@endsection
