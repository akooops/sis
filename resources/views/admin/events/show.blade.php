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
                                    <img src="{{$event->image->fullpath}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>
                            
                            <div class="mb-3">
                                <h4 class="fs-15">Event Name</h4>
                                {{$event->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Event Description</h4>
                                @if(is_null($event->description))
                                    <span class="badge bg-danger">No Description</span>
                                @else
                                    {{$event->description}}
                                @endif 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Created By</h4>
                                @if(is_null($event->user))
                                    <span class="badge bg-primary">Deleted User</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $event->user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $event->user->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Updated By</h4>
                                @if(is_null($event->updatedBy))
                                    <span class="badge bg-primary">Not Updated</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $event->updatedBy->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $event->updatedBy->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>
      
                        </div>
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->

                <div class="text-end mb-3">
                    <a href="{{ route('events.index') }}" class="btn btn-primary w-sm">Back</a>
                    <a href="{{ route('events.edit', $event->id) }}" class="btn btn-success w-sm">Edit</a>
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
