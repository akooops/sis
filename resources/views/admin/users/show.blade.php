@extends('admin.layouts.master')
@section('title') Users @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Users @endslot
@slot('title') Show User @endslot
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
                            <h4 class="card-title mb-0">User Information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$user->image->fullpath}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>
                        
                            <div class="mb-3">
                                <h4 class="fs-15">User Firstname</h4>
                                {{$user->firstname}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User Lastname</h4>
                                {{$user->lastname}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User Username</h4>
                                {{$user->username}}
                            </div>


                            <div class="mb-3">
                                <h4 class="fs-15">User Email</h4>
                                {{$user->email}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User Role</h4>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User Description</h4>
                                @if(is_null($user->description))
                                    <span class="badge bg-danger">No Description</span>
                                @else
                                    {{$user->description}}
                                @endif 
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="text-end mb-3">
                        <a href="{{ route('users.index') }}" class="btn btn-primary w-sm">Back</a>
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-success w-sm">Edit</a>
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
