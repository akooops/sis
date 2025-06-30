@extends('admin.layouts.master')
@section('title') Menus @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Menus @endslot
@slot('title') Create Menu @endslot
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

            <form method="POST" action="{{ route('admin.menus.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Menu name <span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name')}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>                        
                            </div>
                        </div>
                        
                        <div class="text-end mb-3">
                            <a href="{{ route('admin.menus.index') }}" class="btn btn-primary w-sm">Back</a>
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
