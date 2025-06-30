@extends('admin.layouts.master')
@section('title') Events @endsection
@section('css')
<link href="{{ URL::asset('assets/old-admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/old-admin/libs/summernote/summernote-lite.min.css')}}" rel="stylesheet" type="text/css" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .note-editable{
        background-color: #fff
    }
</style>
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Events @endslot
@slot('title') Create Event @endslot
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

            <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.events.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Event name<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Event slug<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Event Starts At</label>
                                    <input name="starts_at" value="{{old('starts_at')}}" type="text" id="datepicker-starts-at" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" data-enable-time="true" readonly="readonly">
                                    @error('starts_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Event Ends At</label>
                                    <input name="ends_at" value="{{old('ends_at')}}" type="text" id="datepicker-ends-at" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" data-enable-time="true" readonly="readonly">
                                    @error('ends_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Event status<span class="text-danger">*</span></label>
                                    <select class="form-control" name="status">
                                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="hidden" {{ old('status') == 'hidden' ? 'selected' : '' }}>Hidden</option>
                                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
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
                                    <label class="form-label" for="">Event thumbnail <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="radio" name="media_option" id="media_option_upload" value="upload" checked>
                                            <label class="form-check-label" for="media_option_upload">Upload Image</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="media_option" id="media_option_select" value="select">
                                            <label class="form-check-label" for="media_option_select">Select from Media</label>
                                        </div>
                                    </div>
                                    
                                    <!-- File upload input -->
                                    <div id="upload_section">
                                        <input type="file" name="file" id="file_input" class="form-control mb-2" accept="image/*">
                                        <div id="file_preview"></div>
                                        @error('file')
                                            <p class="mx-2 my-2 text-danger"><strong>{{ $message }}</strong></p>
                                        @enderror
                                    </div>
                                    
                                    <!-- Media select input -->
                                    <div id="media_section" style="display:none;">
                                        <div class="row">
                                            @foreach($medias as $media)
                                                <div class="col-auto mb-2">
                                                    <label class="d-block">
                                                        <input type="radio" name="media_id" value="{{ $media->id }}" class="d-none media-radio">
                                                        <img src="{{ $media->file->url }}" alt="media" class="img-thumbnail media-thumb" style="width:90px; height:70px; object-fit:cover; cursor:pointer; border:2px solid transparent;">
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('media_id')
                                            <p class="mx-2 my-2 text-danger"><strong>{{ $message }}</strong></p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Event {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Event {{$defaultLanguage->name}} description <span class="text-danger">*</span></label>
                                    <textarea name="description" type="text" class="form-control">{{old('description')}}</textarea>
                                    @error('description')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Event {{$defaultLanguage->name}} content <span class="text-danger">*</span></label>
                                    <textarea id="summernote-editor" name="content" class="form-control">{{old('content')}}</textarea>
                                    @error('content')
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
                            <a href="{{ route('admin.events.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('assets/old-admin/libs/flatpickr/flatpickr.min.js')}}"></script>
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/summernote/summernote-lite.min.js')}}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between upload and select
    document.getElementById('media_option_upload').addEventListener('change', function() {
        document.getElementById('upload_section').style.display = '';
        document.getElementById('media_section').style.display = 'none';
    });
    document.getElementById('media_option_select').addEventListener('change', function() {
        document.getElementById('upload_section').style.display = 'none';
        document.getElementById('media_section').style.display = '';
    });

    // Image preview for upload
    document.getElementById('file_input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('file_preview');
        preview.innerHTML = '';
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="width:120px;height:90px;object-fit:cover;">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Highlight selected media image
    document.querySelectorAll('.media-thumb').forEach(function(img) {
        img.addEventListener('click', function() {
            document.querySelectorAll('.media-thumb').forEach(i => i.style.border = '2px solid transparent');
            this.style.border = '2px solid #0d6efd';
            this.previousElementSibling.checked = true;
        });
    });

    // Initialize Summernote
    $('#summernote-editor').summernote({
        height: 400,
        minHeight: 300,
        maxHeight: 600,
        focus: false,
        codeviewFilter: false,
        codeviewIframeFilter: false,
        disableDragAndDrop: false,
    });

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
