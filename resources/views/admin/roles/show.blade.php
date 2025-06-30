@extends('admin.layouts.master')
@section('title') Roles @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Roles @endslot
@slot('title') Show Role @endslot
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
                            <h4 class="card-title mb-0">Role information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Role name</h4>
                                {{$role->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Role is default?</h4>
                                @if($role->is_default)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Role created at</h4>
                                {{$role->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Role updated at</h4>
                                {{$role->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Role permissionss</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <table class="table table-striped">
                
                                    @foreach($role->permissions  as $permission)
                                        <tr>
                                            <td>{{ $permission->name }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-primary w-sm">Back</a>
                        @haspermission('admin.roles.update')
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-success w-sm">Edit</a>
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
