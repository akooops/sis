@extends('admin.layouts.master')
@section('title') Grades @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Grades @endslot
@slot('title') Grades @endslot
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
                        @haspermission('admin.grades.store')
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.grades.create') }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Grade</a>
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
                            <h4 class="card-title mb-0">Grades</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.grades.index')}}">
                                        <div class="d-flex align-items-center">
                                            <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50 me-2">

                                            <select name="program_id" class="form-control w-auto me-2">
                                                <option value="">All Programs</option>
                                                @foreach($programs as $progam)
                                                    <option value="{{ $progam->id }}" {{ (request('program_id') == $progam->id) ? 'selected' : '' }}>
                                                        {{ $progam->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-search-line search-icon"></i>Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>


                            @haspermission('admin.grades.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Name</th> 
                                            <th scope="col">Slug</th> 
                                            <th scope="col">Program</th> 
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grades as $grade)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$grade->id}}</a>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-2">
                                                        <img src="{{ $grade->thumbnailUrl }}" class="avatar-xs material-shadow">
                                                    </div>
                                                    <div class="flex-grow-1">{{ $grade->name }}</div>
                                                </div>
                                            </td>  

                                            <td>
                                                <span class="badge bg-primary"> {{$grade->slug }} </span>
                                            </td> 
                                            
                                            <td>{{$grade->program->name }}</td>  

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" grade="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.grades.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.grades.show', $grade->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.grades.update')
                                                            <li><a class="dropdown-item" href="{{route('admin.grades.edit', $grade->id)}}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.grades.delete')
                                                            <form action="{{route('admin.grades.destroy', $grade->id)}}" method="POST">
                                                                @csrf
                                                                @method('delete')

                                                                <li><button class="dropdown-item" type="submit"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Destroy</button></li>
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
                            @component('admin.components.pagination', ['route' => 'admin.grades.index', 'pagination' => $pagination])@endcomponent
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
