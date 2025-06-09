@extends('admin.layouts.master')
@section('title') Media  @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Media  @endslot
@slot('title') Media  @endslot
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
                        @haspermission('admin.media.store')
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.media.create') }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Media</a>
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
                            <h4 class="card-title mb-0">Media </h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.media.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.media.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Name</th> 
                                            <th scope="col">Type</th> 
                                            <th scope="col">Main</th> 
                                            <th scope="col">Url</th> 
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($medias as $media)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$media->id}}</a>
                                            </td>

                                            <td>{{ $media->name }}</td>  

                                            <td>{{ $media->type }}</td>  

                                            <td>
                                                @if($media->is_main)
                                                    <span class="badge bg-success">yes</span>                                                    
                                                @else
                                                    <span class="badge bg-danger">no</span>
                                                @endif
                                            </td>

                                            <td>
                                                <a href="{{$media->file->url}}" target="_blank" rel="noopener noreferrer" class="me-2">
                                                    <span class="badge bg-primary">
                                                        <i class="ri-link"></i> Open
                                                    </span>
                                                </a>
                                            </td>

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" media="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.media.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.media.show', $media->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.media.update')
                                                            <li><a class="dropdown-item" href="{{route('admin.media.edit', $media->id)}}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        @endhaspermission

                                                        @if(!$media->mediable)
                                                            @haspermission('admin.media.destroy')
                                                                <form action="{{route('admin.media.destroy', $media->id)}}" method="POST">
                                                                    @csrf
                                                                    @method('delete')

                                                                    <li><button class="dropdown-item" type="submit"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Destroy</button></li>
                                                                </form>
                                                            @endhaspermission
                                                        @endif
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
                            @component('admin.components.pagination', ['route' => 'admin.media.index', 'pagination' => $pagination])@endcomponent
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
