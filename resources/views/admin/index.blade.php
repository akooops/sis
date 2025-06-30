@extends('admin.layouts.master')
@section('title') Dashboard @endsection
@section('css')
<link href="{{ URL::asset('assets/old-admin/libsjsvectormap/jsvectormap.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/old-admin/libs/swiper/swiper.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Dashboards @endslot
@slot('title') Dashboard @endslot
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
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<!-- apexcharts -->
<script src="{{ URL::asset('/assets/old-admin/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('/assets/old-admin/libs/jsvectormap/jsvectormap.min.js') }}"></script>
<script src="{{ URL::asset('/assets/old-admin/libs/swiper/swiper.min.js')}}"></script>
<!-- dashboard init -->
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
