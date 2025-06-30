@extends('admin.layouts.master')
@section('title') Roles @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Roles @endslot
@slot('title') Edit Role @endslot
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

            <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
                @csrf
                @method('patch')
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Role name <span class="text-danger">*</span></label>
                                    <input name="name" value="{{ $role->name }}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="is_default">Make default <span class="text-danger">*</span></label>
                                    <select class="form-control" name="is_default">
                                        <option value="1" {{$role->is_default ? "selected" : ""}}>yes</option>
                                        <option value="0" {{!$role->is_default ? "selected" : ""}}>no</option>
                                    </select>

                                    @error('is_default')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>                                
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Assign Permissions</label>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th scope="col" width="1%">Assign</th>
                                                <th scope="col" width="20%">Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($permissions as $permission)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" 
                                                               name="permissions[]"
                                                               value="{{ $permission->id }}"
                                                               class='permission'
                                                               @if(isset($role) && $role->permissions->contains($permission->id)) checked @endif>
                                                    </td>
                                                    <td>{{ $permission->name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- end card -->
                        

                        <div class="text-end mb-3">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
