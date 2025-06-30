@extends('admin.layouts.master')
@section('title') Documents @endsection
@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Documents @endslot
@slot('title') Create Document @endslot
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.documents.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Document name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name')}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>   
                                
                                <div class="mb-4">
                                    <label class="form-label" for="">Document file<span class="text-danger">*</span></label>
                                    <input type="file" name="file" id="file_input" class="form-control mb-2" accept="*">
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

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Document {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
                                    <input name="title" value="{{old('title')}}" type="text" class="form-control">
                                    @error('title')
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
                            <a href="{{ route('admin.documents.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
