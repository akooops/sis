@extends('admin.layouts.master')
@section('title') Events @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Events @endslot
@slot('title') Show Event @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning , {{Auth::user()->fullname}}</h4>
                        </div>
                    </div><!-- end card header -->
                </div>  
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">                                
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Event Inforamtion</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$eventEdition->event->image->fullpath}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>
                            
                            <div class="mb-3">
                                <h4 class="fs-15">Event Edition Name</h4>
                                {{$eventEdition->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Event Edition Description</h4>
                                @if(is_null($eventEdition->description))
                                    <span class="badge bg-danger">No Description</span>
                                @else
                                    {{$eventEdition->description}}
                                @endif 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Event From - To</h4>
                                {{$eventEdition->duration}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Event Edition Place</h4>
                                @if(is_null($eventEdition->place))
                                    <span class="badge bg-danger">No Place</span>
                                @else
                                    {{$eventEdition->place}}
                                @endif 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Number Of Participants</h4>
                                <span class="badge bg-primary">{{ $eventEdition->participants }}</span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Created By</h4>
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
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Updated By</h4>
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
                            </div>
      
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Event Editions Album</h4>
                        </div>

                        <div class="card-body">
                            <div class="row mb-3">
                                @foreach ($eventEdition->images as $key => $image)
                                <div id="img-{{$key}}" class="col-4 col-md-2">
                                    <figure class="figure" style="width: 100%">
                                        <img src="{{ $image->fullpath}}" class="figure-img img-fluid rounded border" style="width:100%; height: 100px; object-fit:cover">
                                    </figure>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->

                <div class="text-end mb-3">
                    <a href="{{ route('eventEditions.index', $eventEdition->event_id) }}" class="btn btn-primary w-sm">Back</a>
                    <a href="{{ route('eventEditions.edit', $eventEdition->id) }}" class="btn btn-success w-sm">Edit</a>
                </div>
            </div>
            <!-- end row -->
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/admin/js/app.min.js') }}"></script>
@endsection
