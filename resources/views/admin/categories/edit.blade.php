@extends('admin.layouts.master')
@section('title') Catgeories @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Catgeories @endslot
@slot('title') Edit Category @endslot
@endcomponent
<div class="row">
    <div class="col">
        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning, {{Auth::user()->fullname}}</h4>
                        </div>
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <form method="POST" enctype="multipart/form-data" action="{{ route('categories.update', $category->id) }}">
                @method('patch')
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Category Inforamtion</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-error-warning-line me-3 align-middle"></i> Each catergory can be assigned later to a post
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Category Name</label>
                                    <input name="name" value="{{$category->name}}" type="text" class="form-control">
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
                        <!-- end card -->
                    </div>
                    <!-- end col -->

                    <div class="text-end mb-3">
                        <a href="{{ route('categories.index') }}" class="btn btn-primary w-sm">Back</a>
                        <button type="submit" class="btn btn-success w-sm">Submit</button>
                    </div>
                </div>
                <!-- end row -->
            </form>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/admin/js/app.min.js') }}"></script>
@endsection
