@extends('admin.layouts.master')
@section('title') Forms @endsection
@section('css')
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
@slot('li_1') Forms @endslot
@slot('title') Edit Form @endslot
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
                    <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.forms.update', $form->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Form name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name', $form->name)}}" type="text" class="form-control">
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
                                    <input name="slug" value="{{old('slug', $form->slug)}}" type="text" class="form-control">
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
                                        <option value="draft" {{ $form->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="hidden" {{ $form->status == 'hidden' ? 'selected' : '' }}>Hidden</option>
                                        <option value="published" {{ $form->status == 'published' ? 'selected' : '' }}>Published</option>
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
                                    <label class="form-label" for="">Add captcha<span class="text-danger">*</span></label>
                                    <select class="form-control" name="has_captcha">
                                        <option value="1" {{ $form->has_captcha? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !$form->has_captcha ? 'selected' : '' }}>No</option>
                                    </select>
                                    @error('has_captcha')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Submit</button>
                                </div>
                            </div>
                        </div>
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
                                                <input type="text" id="title-{{$language->id}}" name="title" class="form-control translation-title" value="{{$form->getTranslation('title', $language->code)}}">
                                                <div class="translation-error" id="error-title-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="description-{{$language->id}}">Description <span class="text-danger">*</span></label>
                                                <textarea id="description-{{$language->id}}" name="description" class="form-control translation-description" rows="3">{{$form->getTranslation('description', $language->code)}}</textarea>
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
                        <a href="{{ route('admin.forms.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script>

document.addEventListener('DOMContentLoaded', function() {
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
            fetch('{{ route('admin.forms.update-translation', $form->id) }}', {
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
