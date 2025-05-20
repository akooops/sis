@extends('admin.layouts.master')
@section('title') Menu Items @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Menu Items @endslot
@slot('title') Menu Items @endslot
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
                        @haspermission('admin.menu-items.store')
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.menu-items.create', ['menu' => $menu->id]) }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Menu Item</a>
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
                            <h4 class="card-title mb-0">Menu Items</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.menu-items.index', ['menu' => $menu->id])}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.menu-items.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Name</th> 
                                            <th scope="col">Page</th> 
                                            <th scope="col">Url</th>
                                            <th scope="col">Parent</th> 
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($menuItems as $menuItem)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$menuItem->id}}</a>
                                            </td>

                                            <td>{{ $menuItem->name }}</td>  

                                            <td>
                                                @if($menuItem->page)
                                                    <span class="badge bg-primary"> {{$menuItem->page->name }} </span>
                                                @else
                                                    <span class="badge bg-danger"> not linked</span>
                                                @endif
                                            </td>  

                                            <td>
                                                @if(!$menuItem->page)
                                                    <a href="{{$media->url}}" target="_blank" rel="noopener noreferrer" class="me-2">
                                                        <span class="badge bg-primary">
                                                            <i class="ri-link"></i> Open
                                                        </span>
                                                    </a>                                                
                                                @else
                                                    <span class="badge bg-danger"> linked</span>
                                                @endif
                                            </td> 

                                            <td>
                                                @if($menuItem->parent)
                                                    <span class="badge bg-primary"> {{$menuItem->parent->name }} </span>
                                                @else
                                                    <span class="badge bg-danger"> no parent</span>
                                                @endif
                                            </td>  

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" menuItem="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.menu-items.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.menu-items.show', $menuItem->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.menu-items.update')
                                                            <li><a class="dropdown-item" href="{{route('admin.menu-items.edit', $menuItem->id)}}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.menu-items.delete')
                                                            <form action="{{route('admin.menu-items.destroy', $menuItem->id)}}" method="POST">
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
                            <div class="align-items-center justify-content-between d-flex">
                                <div class="flex-shrink-0">
                                    <div class="text-muted">
                                        Showing from {{$pagination['from']}} to {{$pagination['to']}} of {{$pagination['total']}} results
                                    </div>
                                </div>
                                <ul class="pagination pagination-separated pagination-sm mb-0">
                                    <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('admin.menu-items.index', array_merge(['page' => $pagination["prevPage"], 'menu' => $menu->id], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">←</a>
                                    </li>
                                    
                                    @foreach($pagination["pages"] as $page)
                                    <li class="page-item {{($page == $pagination['currentPage']) ? 'active' : ''}}">
                                        <a href="{{ route('admin.menu-items.index', array_merge(['page' => $page, 'menu' => $menu->id], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">
                                            {{$page}}
                                        </a>
                                    </li>
                                    @endforeach

                                    <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('admin.menu-items.index', array_merge(['page' => $pagination["nextPage"], 'menu' => $menu->id], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">→</a>
                                    </li>
                                </ul>
                            </div>
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
