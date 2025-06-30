@extends('admin.layouts.master')
@section('title') Contact Submissions @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Contact Submissions @endslot
@slot('title') Show Contact Submission @endslot
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
                            <h4 class="card-title mb-0">Contact Submission information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission name</h4>
                                {{$contactSubmission->name }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission email</h4>
                                {{$contactSubmission->email }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission phone</h4>
                                {{$contactSubmission->phone }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission subject</h4>
                                {{$contactSubmission->subject }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission message</h4>
                                {{$contactSubmission->message }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission created at</h4>
                                {{$contactSubmission->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Contact Submission updated at</h4>
                                {{$contactSubmission->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.contact-submissions.index') }}" class="btn btn-primary w-sm">Back</a>
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
