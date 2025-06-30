@extends('admin.layouts.master')
@section('title') Permissions @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Permissions @endslot
@slot('title') Show Permission @endslot
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
                            <h4 class="card-title mb-0">Permission Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Permission name</h4>
                                {{$permission->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Permission created at</h4>
                                {{$permission->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Permission updated at</h4>
                                {{$permission->updated_at}}
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
