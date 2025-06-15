@extends('admin.layouts.master')
@section('title') Visit Bookings @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Visit Bookings @endslot
@slot('title') Show Visit Booking @endslot
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

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Visit Booking information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Visit Service name</h4>
                                {{$visitBooking->visitService->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking datetime</h4>
                                <span class="badge bg-primary"> {{$visitBooking->visitTimeSlot->starts_at }} - {{$visitBooking->visitTimeSlot->ends_at }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking Visitors Count</h4>
                                <span class="badge bg-primary"> {{$visitBooking->visitors_count }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking Guardian name</h4>
                                <span class="badge bg-primary"> {{$visitBooking->guardian_name }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking email</h4>
                                <span class="badge bg-primary"> {{$visitBooking->email }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking phone</h4>
                                <span class="badge bg-primary"> {{$visitBooking->phone }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking Student name</h4>
                                <span class="badge bg-primary"> {{$visitBooking->student_name }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking Student grade</h4>
                                <span class="badge bg-primary"> {{$visitBooking->student_grade }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking Student school</h4>
                                <span class="badge bg-primary"> {{$visitBooking->student_school }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking created at</h4>
                                {{$visitBooking->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Booking updated at</h4>
                                {{$visitBooking->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.visit-bookings.index', $visitBooking->visitService->id) }}" class="btn btn-primary w-sm">Back</a>                   
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->    

        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
