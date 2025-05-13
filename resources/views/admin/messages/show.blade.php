@extends('admin.layouts.master')
@section('title') Messages @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Messages @endslot
@slot('title') Show Message @endslot
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
                            <h4 class="card-title mb-0">Message Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h2 class="fs-15">Full Name:</h2>
                                <p>{{ $message->firstname }} {{ $message->lastname }}<p>
                            </div>

                            <div class="mb-3">
                                <h2 class="fs-15">Email:</h2>
                                <p>{{$message->email}}<p>
                            </div>

                            <div class="mb-3">
                                <h2 class="fs-15">Subject:</h2>
                                <p>{{$message->subject}}<p>
                            </div>

                            <div class="mb-3">
                                <h2 class="fs-15">Message:</h2>
                                <p>{{$message->message}}<p>
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="text-end mb-3">
                        <a href="{{ route('messages.index') }}" class="btn btn-primary w-sm">Back</a>
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
