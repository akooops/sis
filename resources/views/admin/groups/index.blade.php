@extends('admin.layouts.master')
@section('title') Groups @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Groups @endslot
@slot('title') Groups @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning, {{Auth::user()->fullname}}</h4>
                        </div>
                        <div class="mt-3 mt-lg-0">
                            @canany(['groups.create'])
                            <div class="row g-3 mb-0 align-items-center">
                                <div class="col-auto">
                                    <a href="{{ route('groups.create') }}" class="btn btn-soft-success"><i
                                            class="ri-add-circle-line align-middle me-1"></i>
                                        Create Group</a>
                                </div>
                                <!--end col-->
                            </div>
                            @endcanany
                            <!--end row-->
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
                            <h4 class="card-title mb-0">Manage Groups</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('groups.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Subscribers Count</th>
                                            <th scope="col">Created By</th>
                                            <th scope="col">Last Updated By</th>
                                            <th scope="col" colspan="3" width="1%">Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groups as $group)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$group->id}}</a>
                                            </td>
                                            <td>{{ $group->name }}</td>
                                            <td>
                                                <span class="badge bg-success"> {{ count($group->Subscribers) }} </span>
                                            </td>
                                            <td>
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
                                            </td>
                                            
                                            <td>
                                                @if(is_null($group->updatedBy))
                                                    <span class="badge bg-primary">Not Updated</span>
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
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    @canany(['groups.show'])
                                                    <div class="show">
                                                        <a href="{{ route('groups.show', $group->id) }}" class="btn btn-sm btn-info show-item-btn">Show</a>
                                                    </div>
                                                    @endcanany
                                                    
                                                    @canany(['groups.edit'])
                                                    <div class="edit">
                                                        <a href="{{ route('groups.edit', $group->id) }}" class="btn btn-sm btn-success edit-item-btn">Edit</a>
                                                    </div>
                                                    @endcanany

                                                    @canany(['groups.destroy'])
                                                    <div class="remove">
                                                        {!! Form::open(['method' => 'DELETE','route' => ['groups.destroy', $group->id],'style'=>'display:inline']) !!}
                                                        {!! Form::submit('Delete', ['class' => 'btn btn-sm btn-danger remove-item-btn']) !!}
                                                        {!! Form::close() !!}
                                                    </div>
                                                    @endcanany
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
                            @component('admin.components.pagination', ['route' => 'groups.index', 'pagination' => $pagination])@endcomponent
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
