@extends('admin.layouts.master')
@section('title') Sponsors @endsection
@section('css')
<link href="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Sponsors @endslot
@slot('title') Create Sponsor @endslot
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('sponsors.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Danger Alert -->
                                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-error-warning-line me-3 align-middle"></i> Fields with * sign are mandatory.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Sponsor Name <span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name')}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Sponsor Url</label>
                                    <input name="url" value="{{old('url')}}" type="text" class="form-control">
                                    @error('url')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Sponsor Image <span class="text-danger">*</span></label>
                                    <input name="file" type="file" class="form-control" accept="image/*">
                                    @error('file')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <!-- end card -->

                        <div class="text-end mb-3">
                            <button type="submit" class="btn btn-success w-sm">Submit</button>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->    
            </form>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.js')}}"></script>

<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
