@extends('admin.layouts.master')
@section('title') Visit Bookings @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Visit Bookings @endslot
@slot('title') Visit Bookings @endslot
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

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Visit Bookings</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('admin.visit-bookings.index', $visitService->id)}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            @haspermission('admin.visit-bookings.index')
                            <div class="table-responsive" style="overflow: visible;">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="10%" width="100px">#</th>
                                            <th scope="col">Date/Time</th> 
                                            <th scope="col">Visitors Count</th> 
                                            <th scope="col">Status</th> 
                                            <th scope="col" width="75px">Actions</th>                                        
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($visitBookings as $visitBooking)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="#" class="fw-semibold">#{{$visitBooking->id}}</a>
                                            </td>

                                            <td>
                                                <span class="badge bg-primary"> {{$visitBooking->starts_at }} </span>
                                            </td>  

                                            <td>
                                                <span class="badge bg-primary"> {{$visitBooking->visitors_count }} </span>
                                            </td>  

                                            <td>
                                                @if($visitBooking->status == 'pending')
                                                    <span class="badge bg-info">Pending</span>   
                                                @elseif($visitBooking->status == 'confirmed')   
                                                    <span class="badge bg-success">Confirmed</span>                                                 
                                                @else
                                                    <span class="badge bg-danger">Canceled</span>
                                                @endif
                                            </td>  

                                            <td>
                                                <div class="dropdown">
                                                    <a href="#" menuItem="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false" class="">
                                                        <i class="ri-more-2-fill"></i>
                                                    </a>

                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1" style="">
                                                        @haspermission('admin.visit-bookings.show')
                                                            <li><a class="dropdown-item" href="{{route('admin.visit-bookings.show', $visitBooking->id)}}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                        @endhaspermission

                                                        @haspermission('admin.visit-bookings.update')
                                                            @if($visitBooking->status !== 'confirmed')
                                                            <li>
                                                                <form action="{{route('admin.visit-bookings.update', $visitBooking->id)}}" method="POST" class="status-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="confirmed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="ri-check-line align-bottom me-2 text-success"></i> 
                                                                        <span class="text-success">Mark as Confirmed</span>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            @endif
                                                            
                                                            @if($visitBooking->status !== 'pending')
                                                            <li>
                                                                <form action="{{route('admin.visit-bookings.update', $visitBooking->id)}}" method="POST" class="status-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="pending">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="ri-time-line align-bottom me-2 text-info"></i> 
                                                                        <span class="text-info">Mark as Pending</span>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            @endif
                                                            
                                                            @if($visitBooking->status !== 'canceled')
                                                            <li>
                                                                <form action="{{route('admin.visit-bookings.update', $visitBooking->id)}}" method="POST" class="status-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="canceled">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="ri-close-line align-bottom me-2 text-danger"></i> 
                                                                        <span class="text-danger">Mark as Canceled</span>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            @endif
                                                        @endhaspermission


                                                        @haspermission('admin.visit-bookings.destroy')
                                                            <form action="{{route('admin.visit-bookings.destroy', $visitBooking->id)}}" method="POST">
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
                                        <a href="{{ route('admin.visit-bookings.index', array_merge(['page' => $pagination["prevPage"], 'visitService' => $visitService->id], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">←</a>
                                    </li>
                                    
                                    @foreach($pagination["pages"] as $page)
                                    <li class="page-item {{($page == $pagination['currentPage']) ? 'active' : ''}}">
                                        <a href="{{ route('admin.visit-bookings.index', array_merge(['page' => $page, 'visitService' => $visitService->id], request()->only('search', 'perPage'))) }}" 
                                            class="page-link">
                                            {{$page}}
                                        </a>
                                    </li>
                                    @endforeach

                                    <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('admin.visit-bookings.index', array_merge(['page' => $pagination["nextPage"], 'visitService' => $visitService->id], request()->only('search', 'perPage'))) }}" 
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
