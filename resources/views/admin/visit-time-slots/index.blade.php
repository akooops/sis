@extends('admin.layouts.master')
@section('title') Grades @endsection
@section('css')
<link href="{{ URL::asset('assets/admin/libs/calendar/main.min.css')}}" rel="stylesheet" type="text/css" />
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
                        @haspermission('admin.visit-time-slots.store')
                        <div class="mt-3 mt-lg-0">
                            <form action="javascript:void(0);">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.visit-time-slots.create', $visitService->id) }}" class="btn btn-soft-success"><i
                                                class="ri-add-circle-line align-middle me-1"></i>
                                            Add Visit Time Slot</a>
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
                            <h4 class="card-title mb-0">Visit time slots</h4>
                        </div>
                        <div class="card-body">
                            @haspermission('admin.visit-time-slots.index')
                                <div id="calendar"></div>
                            @endhaspermission
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
<script src="{{ URL::asset('assets/libs/calendar/main.min.js')}}"></script>
<script src='{{ URL::asset('assets/libs/calendar/locales-all.min.js')}}'></script>

<script>

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridWeek', // Set default view
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek'
    },
    events: [
      @foreach($visitTimeSlots as $visitTimeSlot)
      {
        title: '{{$visitTimeSlot->starts_at}}',
        start: '{{$visitTimeSlot->starts_at}}',
        url: '{{route("admin.visit-time-slots.edit", $visitTimeSlot->id)}}'
      },
      @endforeach
    ]
  });

  calendar.render();
});

</script>
@endsection