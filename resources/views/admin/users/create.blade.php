@extends('admin.layouts.master')
@section('title') Users @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Users @endslot
@slot('title') Create User @endslot
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3 d-flex">
                                    <div class="col-12 col-sm-6 pe-2">
                                        <label class="form-label" for="">User Firstname <span class="text-danger">*</span></label>
                                        <input name="firstname" value="{{old('firstname')}}" type="text" class="form-control">
                                        @error('firstname')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-sm-6 ps-2">
                                        <label class="form-label" for="">User Lastname <span class="text-danger">*</span></label>
                                        <input name="lastname" value="{{old('lastname')}}" type="text" class="form-control">
                                        @error('lastname')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">User Username <span class="text-danger">*</span></label>
                                    <input name="username" value="{{old('username')}}" type="text" class="form-control">
                                    @error('username')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">User Email <span class="text-danger">*</span></label>
                                    <input name="email" value="{{old('email')}}" type="text" class="form-control">
                                    @error('email')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">User password <span class="text-danger">*</span></label>
                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                        <input name="password" value="{{old('password')}}" type="password"  id="password-input" class="form-control">
                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                        @error('password')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">User avatar</label>
                                    <input name="file" type="file" class="form-control" accept="image/*">
                                    @error('file')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <!-- end card -->

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Assign Roles</label>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th scope="col" width="1%">Assign</th>
                                                <th scope="col" width="20%">Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($roles as $role)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" 
                                                               name="roles[]"
                                                               value="{{ $role->id }}"
                                                               class='roles'
                                                               @if(isset($user) && $user->roles->contains($role->id)) checked @endif>
                                                    </td>
                                                    <td>{{ $role->name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- end card -->

                        <div class="text-end mb-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-primary w-sm">Back</a>
                            <button type="submit" class="btn btn-success w-sm">Submit</button>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->

            </form>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/js/pages/password-addon.init.js') }}"></script>
@endsection
