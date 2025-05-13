@extends('admin.layouts.master')
@section('title') {{$event->name}} Editions @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') {{$event->name}} Editions @endslot
@slot('title') Event Editions @endslot
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
                            @canany(['eventEditions.create'])
                            <div class="row g-3 mb-0 align-items-center">
                                <div class="col-auto">
                                    <a href="{{ route('eventEditions.create', $event->id) }}" class="btn btn-soft-success"><i
                                            class="ri-add-circle-line align-middle me-1"></i>
                                        Create Edition</a>
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
                        <div class="card-header d-flex align-items-center">
                            <img src="{{$event->image->fullpath}}" alt="" class="rounded-circle avatar-sm" style="object-fit: cover; border: 1px solid #000">
                            <h4 class="card-title ms-2 mb-0">Manage <span class='text-primary'><a href="{{route('events.index')}}">{{$event->name}}</a></span> Editions</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('eventEditions.index', $event->id)}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-checkbox">
                                                    <label class="form-check-label" for="responsivetableCheck"></label>
                                                </div>
                                            </th>
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">From - To</th>
                                            <th scope="col">Number Of Participants</th>
                                            <th scope="col">Created By</th>
                                            <th scope="col">Last Updated By</th>
                                            <th scope="col" colspan="3" width="1%">Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($eventEditions as $eventEdition)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $eventEdition->id }}" oninput="checkRow()">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </th>
                                            <td>
                                                <a href="#" class="fw-semibold">#{{$eventEdition->id}}</a>
                                            </td>    
                                            
                                            <td>{{ $eventEdition->name }}</td>
                                            <td>{{ $eventEdition->duration }}</td>
                                            <td> 
                                                <span class="badge bg-primary">{{ $eventEdition->participants }}</span>
                                            </td>

                                            <td>
                                                @if(is_null($eventEdition->user))
                                                    <span class="badge bg-primary">Deleted User</span>
                                                @else
                                                    <div class="d-flex align-items-center">            
                                                        <div class="flex-shrink-0">
                                                            <img src="{{ $eventEdition->user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                                        </div>            
                                                        <div class="flex-grow-1 ms-2 name">
                                                            {{ $eventEdition->user->fullname }}
                                                        </div>            
                                                    </div>   
                                                @endif                                          
                                            </td>
                                            
                                            <td>
                                                @if(is_null($eventEdition->updatedBy))
                                                    <span class="badge bg-primary">Not Updated</span>
                                                @else
                                                    <div class="d-flex align-items-center">            
                                                        <div class="flex-shrink-0">
                                                            <img src="{{ $eventEdition->updatedBy->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                                        </div>            
                                                        <div class="flex-grow-1 ms-2 name">
                                                            {{ $eventEdition->updatedBy->fullname }}
                                                        </div>            
                                                    </div>   
                                                @endif                                          
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2">
                                                    @canany(['eventEditions.show'])
                                                    <div class="show">
                                                        <a href="{{ route('eventEditions.show', $eventEdition->id) }}" class="btn btn-sm btn-info show-item-btn">Show</a>
                                                    </div>
                                                    @endcanany
                                                    
                                                    @canany(['eventEditions.edit'])
                                                    <div class="edit">
                                                        <a href="{{ route('eventEditions.edit', $eventEdition->id) }}" class="btn btn-sm btn-success edit-item-btn">Edit</a>
                                                    </div>
                                                    @endcanany

                                                    @canany(['eventEditions.destroy'])
                                                    <div class="remove">
                                                        {!! Form::open(['method' => 'DELETE','route' => ['eventEditions.destroy', $eventEdition->id],'style'=>'display:inline']) !!}
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
                            <div class="align-items-center justify-content-between d-flex">
                                <div class="flex-shrink-0">
                                    <div class="text-muted">
                                        Showing from {{$pagination['from']}} to {{$pagination['to']}} of {{$pagination['total']}} results
                                    </div>
                                </div>
                                <ul class="pagination pagination-separated pagination-sm mb-0">
                                    <li class="page-item {{is_null($pagination["prev_page"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('eventEditions.index' , array_merge(['event' => $event->id, 'page' => $pagination["prev_page"]], request()->only('search', 'perPage', 'dropped'))) }}" 
                                            class="page-link">←</a>
                                    </li>
                                    
                                    @foreach($pagination["pages"] as $page)
                                    <li class="page-item {{($page == $pagination['current_page']) ? 'active' : ''}}">
                                        <a href="{{ route('eventEditions.index' , array_merge(['event' => $event->id, 'page' => $page], request()->only('search', 'perPage', 'dropped'))) }}" 
                                            class="page-link">
                                            {{$page}}
                                        </a>
                                    </li>
                                    @endforeach
                            
                                    <li class="page-item {{is_null($pagination["next_page"]) ? 'disabled' : ''}}">
                                        <a href="{{ route('eventEditions.index' , array_merge(['event' => $event->id, 'page' => $pagination["next_page"]], request()->only('search', 'perPage', 'dropped'))) }}" 
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
