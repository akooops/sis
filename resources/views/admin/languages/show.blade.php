@extends('admin.layouts.master')
@section('title') Languages @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Languages @endslot
@slot('title') Show Language @endslot
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
                            <h4 class="card-title mb-0">Language information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Language name</h4>
                                {{$language->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Language code</h4>
                                {{$language->code}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Language is default?</h4>
                                @if($language->is_default)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Language is right to left?</h4>
                                @if($language->is_rtl)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Language created at</h4>
                                {{$language->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Language updated at</h4>
                                {{$language->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.languages.index') }}" class="btn btn-primary w-sm">Back</a>
                        @haspermission('admin.languages.update')
                            <a href="{{ route('admin.languages.edit', $language->id) }}" class="btn btn-success w-sm">Edit</a>
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
