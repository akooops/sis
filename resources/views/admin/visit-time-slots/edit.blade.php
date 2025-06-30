@extends('admin.layouts.master')
@section('title') Visit Time Slots @endsection
@section('css')
<link href="{{ URL::asset('assets/old-admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Visit Time Slots @endslot
@slot('title') Edit Visit Time Slot @endslot
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
                    </div>
                </div>
            </div>

            @error('visit_time_slot')
            <div class="mt-2">
                <div class="alert alert-danger" role="alert">
                    <i class="fa fa-check"></i>
                    {{$message}}
                </div>
            </div>
            @enderror

            <!-- MAIN UPDATE FORM -->
            <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.visit-time-slots.update', $visitTimeSlot->id) }}">
                @csrf
                @method('PATCH')
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Visit Time Slot Starts At</label>
                                    <input name="starts_at" value="{{$visitTimeSlot->starts_at}}" type="text" id="datepicker-from-input" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" data-enable-time="true" readonly="readonly">
                                    @error('starts_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>{{$message}}</strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Visit Time Slot Ends At</label>
                                    <input name="ends_at" value="{{$visitTimeSlot->ends_at}}" type="text" id="datepicker-to-input" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" data-enable-time="true" readonly="readonly">
                                    @error('ends_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>{{$message}}</strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Visit Time Slot capacity<span class="text-danger">*</span></label>
                                    <input name="capacity" value="{{$visitTimeSlot->capacity}}" type="number" min="0" class="form-control">
                                    @error('capacity')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>{{$message}}</strong>
                                        </p>
                                    @enderror
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <!-- END MAIN UPDATE FORM -->

            <!-- ACTION BUTTONS - OUTSIDE THE MAIN FORM -->
            <div class="text-end mb-3">
                <a href="{{ route('admin.visit-time-slots.index', $visitTimeSlot->visit_service_id) }}" class="btn btn-primary w-sm">Back</a>
                
                @haspermission('admin.visit-time-slots.destroy')
                    <!-- DELETE FORM - NOW SEPARATE AND OUTSIDE MAIN FORM -->
                    <form action="{{route('admin.visit-time-slots.destroy', $visitTimeSlot->id)}}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this time slot?')">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-danger w-sm">Delete</button>
                    </form>
                @endhaspermission
                
                <!-- SUBMIT BUTTON FOR MAIN FORM -->
                <button type="submit" form="main-form" class="btn btn-success w-sm">Submit</button>
            </div>

        </div>
    </div>
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/old-admin/libs/flatpickr/flatpickr.min.js')}}"></script>
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
