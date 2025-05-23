@extends('admin.layouts.master')
@section('title') Banners @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Banners @endslot
@slot('title') Banners @endslot
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
                                    @haspermission('admin.banners.order')
                                    <div class="col-auto">
                                        <a href="{{ route('admin.banners.order-page') }}" class="btn btn-soft-primary"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Order Banners</a>
                                    </div>
                                    <!--end col-->
                                    @endhaspermission

                                    @haspermission('admin.banners.store')
                                    <div class="col-auto">
                                        <a href="{{ route('admin.banners.create') }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Banner</a>
                                    </div>
                                    <!--end col-->
                                    @endhaspermission
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
                            <h4 class="card-title mb-0">Banners</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.banners.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.banners.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Name</th> 
                                            <th scope="col">Page</th> 
                                            <th scope="col">Url</th>
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($banners as $banner)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$banner->id}}</a>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-2">
                                                        <img src="{{ $banner->thumbnailUrl }}" class="avatar-xs material-shadow">
                                                    </div>
                                                    <div class="flex-grow-1">{{ $banner->name }}</div>
                                                </div>
                                            </td>  

                                            <td>
                                                @if($banner->page)
                                                    <span class="badge bg-primary"> {{$banner->page->name }} </span>
                                                @else
                                                    <span class="badge bg-danger"> not linked</span>
                                                @endif
                                            </td>  

                                            <td>
                                                @if(!$banner->page)
                                                    <a href="{{$banner->url}}" target="_blank" rel="noopener noreferrer" class="me-2">
                                                        <span class="badge bg-primary">
                                                            <i class="ri-link"></i> Open
                                                        </span>
                                                    </a>                                                
                                                @else
                                                    <span class="badge bg-danger"> linked</span>
                                                @endif
                                            </td> 


                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" banner="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.banners.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.banners.show', $banner->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.banners.update')
                                                            <li><a class="dropdown-item" href="{{route('admin.banners.edit', $banner->id)}}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.banners.delete')
                                                            <form action="{{route('admin.banners.destroy', $banner->id)}}" method="POST">
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
                            @component('admin.components.pagination', ['route' => 'admin.banners.index', 'pagination' => $pagination])@endcomponent
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
