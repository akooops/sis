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
                            <h4 class="card-title mb-0">User information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$user->avatarUrl}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
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
                                <h4 class="fs-15">Azure user?</h4>
                                @if($user->azure_ad_id)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User created at</h4>
                                {{$user->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">User updated at</h4>
                                {{$user->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">User roles</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <table class="table table-striped">
                
                                    @foreach($user->roles  as $role)
                                        <tr>
                                            <td>{{ $role->name }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary w-sm">Back</a>
                
                        @haspermission('admin.users.update')
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-success w-sm">Edit</a>
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
