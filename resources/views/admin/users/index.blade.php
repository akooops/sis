@extends('admin.layouts.master')
@section('title') Users @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Users @endslot
@slot('title') Users @endslot
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
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('users.create') }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add User</a>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Add, Edit &amp; Remove</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('users.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%"width="100px">#</th>
                                            <th scope="col" width="15%">Name</th>
                                            <th scope="col" width="15%">Username</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Role</th>
                                            <th scope="col" colspan="3" width="1%">Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$user->id}}</a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">            
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ $user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                                    </div>            
                                                    <div class="flex-grow-1 ms-2 name">
                                                        {{ $user->fullname }}
                                                    </div>            
                                                </div> 
                                            </td>
                                            <td>{{ $user->username }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @foreach($user->roles as $role)
                                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                                @endforeach
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2">
                                                    <div class="show">
                                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-info show-item-btn">Show</a>
                                                    </div>
                                                    <div class="edit">
                                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-success edit-item-btn">Edit</a>
                                                    </div>
                                                    <div class="remove">
                                                        {!! Form::open(['method' => 'DELETE','route' => ['users.destroy', $user->id],'style'=>'display:inline']) !!}
                                                        {!! Form::submit('Delete', ['class' => 'btn btn-sm btn-danger remove-item-btn']) !!}
                                                        {!! Form::close() !!}
                                                    </div>
                                                </div> 
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!-- end table -->
                            </div>
                            <!-- end table responsive -->
                        </div>

                        <div class="card-footer">
                            @component('admin.components.pagination', ['route' => 'users.index', 'pagination' => $pagination])@endcomponent
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
