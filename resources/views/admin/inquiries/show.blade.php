@extends('admin.layouts.master')
@section('title') Inquiries @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Inquiries @endslot
@slot('title') Show Inquiry @endslot
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
                            <h4 class="card-title mb-0">Inquiry information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Guardian name</h4>
                                {{$inquiry->guardian_name }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry email</h4>
                                {{$inquiry->email }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry phone</h4>
                                {{$inquiry->phone }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Student name</h4>
                                {{$inquiry->student_name }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Student birthdate</h4>
                                {{$inquiry->student_birthdate }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Student school</h4>
                                {{$inquiry->student_school }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Academic year applied for</h4>
                                {{$inquiry->academic_year_applied }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Grade applied for</h4>
                                {{$inquiry->grade_applied }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry Questions</h4>
                                {{$inquiry->questions }} 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry created at</h4>
                                {{$inquiry->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Inquiry updated at</h4>
                                {{$inquiry->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.inquiries.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
