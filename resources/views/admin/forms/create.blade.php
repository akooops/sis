@extends('admin.layouts.master')
@section('title') Forms @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Forms @endslot
@slot('title') Create Form @endslot
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

            <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.forms.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Form name<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Form slug<span class="text-danger">*</span></label>
                                    <input name="slug" value="{{old('slug')}}" type="text" class="form-control">
                                    @error('slug')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="mb-4">
                                    <label class="form-label" for="">Form status<span class="text-danger">*</span></label>
                                    <select class="form-control" name="status">
                                        <option value="draft">Draft</option>
                                        <option value="hidden">Hidden</option>
                                        <option value="published">Published</option>
                                    </select>

                                    @error('status')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="mb-4">
                                    <label class="form-label" for="has_captcha">Add captcha <span class="text-danger">*</span></label>
                                    <select class="form-control" name="has_captcha">
                                        <option value="1">yes</option>
                                        <option value="0">no</option>
                                    </select>

                                    @error('has_captcha')
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
                                    <label class="form-label" for="">Form {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
                                    <input name="title" value="{{old('title')}}" type="text" class="form-control">
                                    @error('title')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Form {{$defaultLanguage->name}} description <span class="text-danger">*</span></label>
                                    <textarea name="description" type="text" class="form-control">{{old('description')}}</textarea>
                                    @error('description')
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
                            <a href="{{ route('admin.forms.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle name to slug conversion
    const nameInput = document.querySelector('input[name="name"]');
    const slugInput = document.querySelector('input[name="slug"]');
    
    // Flag to track if slug has been manually edited
    let slugManuallyEdited = false;
    
    // Function to convert string to slug
    function stringToSlug(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-')     // Replace spaces with hyphens
            .replace(/-+/g, '-')      // Replace multiple hyphens with single hyphen
            .trim();                  // Trim leading/trailing spaces
    }
    
    // Update slug when name changes (if not manually edited)
    nameInput.addEventListener('input', function() {
        if (!slugManuallyEdited) {
            slugInput.value = stringToSlug(this.value);
        }
    });
    
    // Set flag when slug is manually edited
    slugInput.addEventListener('input', function() {
        slugManuallyEdited = true;
    });
});
</script>
@endsection
