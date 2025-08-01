@extends('admin.layouts.master')
@section('title') Albums @endsection
@section('css')
<link href="{{ URL::asset('assets/old-admin/libs/dropzone/dropzone.min.css')}}" rel="stylesheet" type="text/css" />

<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .translation-error {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    .translation-success {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: none;
    }
</style>
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Albums @endslot
@slot('title') Edit Album @endslot
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

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <!-- Success alert for AJAX responses -->
            <div class="alert alert-success alert-dismissible fade translation-success" role="alert" id="translationSuccess">
                <strong>Success!</strong> <span id="successMessage">Translation updated successfully.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="row">
                <div class="col-12">
                    <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.albums.update', $album->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Album name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name', $album->name)}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  
                                
                                <div class="mb-4">
                                    <label class="form-label" for="">Album slug<span class="text-danger">*</span></label>
                                    <input name="slug" value="{{old('slug', $album->slug)}}" type="text" class="form-control">
                                    @error('slug')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                
                                <div class="mb-4">
                                    <label class="form-label" for="">Album status<span class="text-danger">*</span></label>
                                    <select class="form-control" name="status">
                                        <option value="draft" {{ $album->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="hidden" {{ $album->status == 'hidden' ? 'selected' : '' }}>Hidden</option>
                                        <option value="published" {{ $album->status == 'published' ? 'selected' : '' }}>Published</option>
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
                                    <label class="form-label" for="">Album thumbnail <span class="text-danger">(Keep it empty if you don't want to change it)</span></label>
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
                                
                                <div class="mt-4">
                                    <figure class="figure">
                                        <img src="{{ $album->thumbnailUrl }}" class="rounded avatar-xl" style="object-fit: cover">
                                        <figcaption class="figure-caption">Last uploaded image</figcaption>
                                    </figure>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Album files</label>

                                    <div class="dropzone">
                                        <div class="fallback">
                                            <input name="images" type="file" multiple="multiple">
                                        </div>
                                        <div class="dz-message needsclick">
                                            <div class="mb-3">
                                                <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                                            </div>

                                            <h4>Drop files here or click to upload.</h4>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0" id="dropzone-preview">
                                        <li class="mt-2" id="dropzone-preview-list">
                                            <!-- This is used as the file preview template -->
                                            <div class="border rounded">
                                                <div class="d-flex p-2">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar-sm bg-light rounded">
                                                            <img data-dz-thumbnail class="img-fluid rounded d-block" src="#" alt="Dropzone-Image" />
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="pt-1">
                                                            <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                                            <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                                            <div class="progress animated-progress custom-progress mb-4">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                                                            </div>
                                                            <strong class="error text-danger" data-dz-errormessage></strong>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-3">
                                                        <button data-dz-remove class="btn btn-sm btn-danger">Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                    <!-- end dropzon-preview -->
                                </div>

                                <div class="row mb-3">
                                    <label class="form-label" for="">Last uploaded album images</label>

                                    @foreach ($album->files as $key => $file)
                                    <div id="file-{{$key}}" class="col-4 col-md-2">
                                        <figure class="figure" style="width: 100%">
                                            <img src="{{$file->url}}" class="figure-img img-fluid rounded border" style="width:100%; height: 100px; object-fit:cover">
                                            <span onclick="confirmDelete('{{ $file->id }}', {{ $key }})" class="position-absolute topbar-badge fs-8 translate-middle badge rounded-pill bg-danger" style="cursor: pointer">x</span>
                                        </figure>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Submit</button>
                                </div>
                            </div>
                        </div>

                        @foreach ($album->files as $file)
                            <input type="hidden" name="files[]" value="{{$file->id}}">
                        @endforeach
                    </form>

                    
                    <!-- Success alert for AJAX responses -->
                    <div class="alert alert-success alert-dismissible fade translation-success" role="alert" id="translationSuccess">
                        <strong>Success!</strong> <span id="successMessage">Translation updated successfully.</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-success mb-3" role="tablist">
                                @foreach ($languages as $key => $language)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{$key == 0 ? "active" : ""}}" data-bs-toggle="tab" href="#{{$language->code}}" role="tab" aria-selected="true">
                                            <i class="ri-translate align-middle me-1"></i> {{$language->name}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <form class="translation-form" data-language="{{$language->code}}" data-language-id="{{$language->id}}">
                                            @csrf
                                            <input type="hidden" name="language_id" value="{{$language->id}}">

                                            <div class="mb-4">
                                                <label class="form-label" for="title-{{$language->id}}">Title <span class="text-danger">*</span></label>
                                                <input type="text" id="title-{{$language->id}}" name="title" class="form-control translation-title" value="{{$album->getTranslation('title', $language->code)}}">
                                                <div class="translation-error" id="error-title-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="description-{{$language->id}}">Description <span class="text-danger">*</span></label>
                                                <textarea id="description-{{$language->id}}" name="description" class="form-control translation-description" rows="3">{{$album->getTranslation('description', $language->code)}}</textarea>
                                                <div class="translation-error" id="error-description-{{$language->id}}"></div>
                                            </div>

                                            <div class="text-end mt-3">
                                                <button type="submit" class="btn btn-success w-sm translation-submit">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mb-3">
                        <a href="{{ route('admin.albums.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('assets/old-admin/libs/dropzone/dropzone.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/filepond/filepond.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') }}"></script>
<script src="{{ URL::asset('assets/old-admin/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') }}"></script>

<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
<script>
    
function confirmDelete(fileId, key) {
    if (confirm('Are you sure you want to remove this file?')) {
        // Remove the hidden input
        const inputs = document.querySelectorAll('input[name="files[]"]');
        inputs.forEach(input => {
            if (input.value === fileId.toString()) {
                input.remove();
            }
        });
        
        // Remove the visual element
        const listItem = document.getElementById(`file-${key}`);
        if (listItem) {
            listItem.remove();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var previewTemplate,
        dropzone,
        dropzonePreviewNode = document.querySelector("#dropzone-preview-list");
    (dropzonePreviewNode.id = ""),
        dropzonePreviewNode &&
            ((previewTemplate = dropzonePreviewNode.parentNode.innerHTML),
            dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode),
            (dropzone = new Dropzone(".dropzone", { url: '{{ route('admin.files.upload') }}', method: "post", headers: {'x-csrf-token': '{{csrf_token()}}'}, acceptedFiles: "image/*", previewTemplate: previewTemplate, previewsContainer: "#dropzone-preview" }))),
        FilePond.registerPlugin(FilePondPluginFileEncode, FilePondPluginFileValidateSize, FilePondPluginImageExifOrientation, FilePondPluginImagePreview);
    var inputMultipleElements = document.querySelectorAll("input.filepond-input-multiple");
    inputMultipleElements &&
        (Array.from(inputMultipleElements).forEach(function (e) {
            FilePond.create(e);
        }),
        FilePond.create(document.querySelector(".filepond-input-circle"), {
            labelIdle: 'Drag & Drop your picture or <span class="filepond--label-action">Browse</span>',
            imagePreviewHeight: 170,
            imageCropAspectRatio: "1:1",
            imageResizeTargetWidth: 200,
            imageResizeTargetHeight: 200,
            stylePanelLayout: "compact circle",
            styleLoadIndicatorPosition: "center bottom",
            styleProgressIndicatorPosition: "right bottom",
            styleButtonRemoveItemPosition: "left bottom",
            styleButtonProcessItemPosition: "right bottom",
        }));

    dropzone.on('uploadprogress', function(file, progress, bytesSent){
        if (file.previewElement) 
            file.previewElement.querySelector("[data-dz-uploadprogress]").setAttribute('area-valuenow', Math.round(progress) + "%");
    });

    dropzone.on("success", function(file, response) {
        // Example: extract the file ID and do something with it
        if (response.status === "success" && response.data && response.data.file) {
            console.log(response.data.file.id);
            var fileId = response.data.file.id;

            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'files[]';
            hiddenInput.value = fileId;
            document.querySelector('#main-form').appendChild(hiddenInput);
        }
    });

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

    // Auto slug generation
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

    // Handle translation form submissions
    const forms = document.querySelectorAll('.translation-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const languageId = this.dataset.languageId;
            const languageCode = this.dataset.language;
            const titleInput = document.getElementById(`title-${languageId}`);
            const descriptionTextarea = document.getElementById(`description-${languageId}`);
            const submitButton = this.querySelector('.translation-submit');
            
            // Reset validation state
            titleInput.classList.remove('is-invalid');
            descriptionTextarea.classList.remove('is-invalid');
            document.getElementById(`error-title-${languageId}`).textContent = '';
            document.getElementById(`error-description-${languageId}`).textContent = '';
            
            // Show loading spinner on button
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            submitButton.disabled = true;
            
            // Get form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('_method', 'PATCH');
            formData.append('language_id', languageId);
            formData.append('title', titleInput.value);
            formData.append('description', descriptionTextarea.value);
            
            // Send AJAX request
            fetch('{{ route('admin.albums.update-translation', $album->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw data;
                    });
                }
                return response.json();
            })
            .then(data => {
                // Show success message
                const successAlert = document.getElementById('translationSuccess');
                document.getElementById('successMessage').textContent = `Translation for ${languageCode} updated successfully.`;
                successAlert.classList.add('show');
                
                // Hide after 3 seconds
                setTimeout(() => {
                    successAlert.classList.remove('show');
                }, 3000);
            })
            .catch(error => {
                // Handle validation errors
                if (error.errors) {
                    if (error.errors.title) {
                        titleInput.classList.add('is-invalid');
                        document.getElementById(`error-title-${languageId}`).textContent = error.errors.title[0];
                    }
                    
                    if (error.errors.description) {
                        descriptionTextarea.classList.add('is-invalid');
                        document.getElementById(`error-description-${languageId}`).textContent = error.errors.description[0];
                    }
                } else {
                    // General error
                    document.getElementById(`error-title-${languageId}`).textContent = 'An error occurred. Please try again.';
                }
            })
            .finally(() => {
                // Restore button state
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            });
        });
    });
});

</script>
@endsection
