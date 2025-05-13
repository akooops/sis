@extends('admin.layouts.master')
@section('title') Roles @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Roles @endslot
@slot('title') Roles @endslot
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
                        @haspermission('admin.roles.store')
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.roles.create') }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Role</a>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                        @endhaspermission
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
                            <h4 class="card-title mb-0">Roles</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.roles.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.roles.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Name</th> 
                                            <th scope="col">Default</th> 
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($roles as $role)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$role->id}}</a>
                                            </td>

                                            <td>{{ $role->name }}</td>  
                                            <td>
                                                @if($role->is_default)
                                                    <span class="badge bg-success">yes</span>                                                    
                                                @else
                                                    <span class="badge bg-danger">no</span>
                                                @endif
                                            </td>  

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" role="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.roles.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.roles.show', $role->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.roles.update')
                                                            <li><a class="dropdown-item" href="{{route('admin.roles.edit', $role->id)}}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.roles.delete')
                                                            <form action="{{route('admin.roles.destroy', $role->id)}}" method="DELETE">
                                                                @csrf
                                                                @method('DELETE')

                                                                <li><a class="dropdown-item" type="submit"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Destroy</a></li>
                                                            </form>
                                                        @endhaspermission
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!-- end table -->
                            </div>
                            <!-- end table responsive -->
                            @endhaspermission
                        </div>

                        <div class="card-footer">
                            @component('admin.components.pagination', ['route' => 'admin.roles.index', 'pagination' => $pagination])@endcomponent
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
