@extends('admin.layouts.master')
@section('title') Grades @endsection
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
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
@slot('li_1') Grades @endslot
@slot('title') Edit Grade @endslot
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
                    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.grades.update', $grade->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Grade program<span class="text-danger">*</span></label>
                                    <select class="form-control" name="program_id">
                                        @foreach($programs as $progam)
                                            <option value="{{ $progam->id }}" {{ $grade->program_id == $progam->id ? 'selected' : '' }}>
                                                {{ $progam->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('program_id')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="mb-4">
                                    <label class="form-label" for="">Grade name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name', $grade->name)}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  
                                
                                <div class="mb-4">
                                    <label class="form-label" for="">Grade slug<span class="text-danger">*</span></label>
                                    <input name="slug" value="{{old('slug', $grade->slug)}}" type="text" class="form-control">
                                    @error('slug')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="mb-4">
                                    <label class="form-label" for="">Grade thumbnail <span class="text-danger">(Keep it empty if you don't want to change it)</span></label>
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
                                            @foreach($imageMedias as $imageMedia)
                                                <div class="col-auto mb-2">
                                                    <label class="d-block">
                                                        <input type="radio" name="media_id" value="{{ $imageMedia->id }}" class="d-none media-radio">
                                                        <img src="{{ $imageMedia->file->url }}" alt="media" class="img-thumbnail media-thumb" style="width:90px; height:70px; object-fit:cover; cursor:pointer; border:2px solid transparent;">
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
                                        <img src="{{ $grade->thumbnailUrl }}" class="rounded avatar-xl" style="object-fit: cover">
                                        <figcaption class="figure-caption">Last uploaded image</figcaption>
                                    </figure>
                                </div>

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="card">
                        <div class="card-body">
                            @foreach ($languages as $language)
                                <input type="file" id="image-upload-{{$language->id}}" accept="image/*" style="display: none;">
                            @endforeach

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
                                                <input type="text" id="title-{{$language->id}}" name="title" class="form-control translation-title" value="{{$grade->getTranslation('title', $language->code)}}">
                                                <div class="translation-error" id="error-title-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="description-{{$language->id}}">Description <span class="text-danger">*</span></label>
                                                <textarea id="description-{{$language->id}}" name="description" class="form-control translation-description" rows="3">{{$grade->getTranslation('description', $language->code)}}</textarea>
                                                <div class="translation-error" id="error-description-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="content-{{$language->id}}">Content <span class="text-danger">*</span></label>
                                                <textarea id="content-{{$language->id}}" name="content" class="form-control translation-content markdown-editor">{{$grade->getTranslation('content', $language->code)}}</textarea>
                                                <div class="translation-error" id="error-content-{{$language->id}}"></div>
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
                        <a href="{{ route('admin.grades.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
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

    // Initialize editors object to store references
    const editors = {};
    
    // Initialize SimpleMDE on visible tab first
    const activeTab = document.querySelector('.tab-pane.active');
    if (activeTab) {
        const editorElement = activeTab.querySelector('.markdown-editor');
        if (editorElement) {
            const languageId = editorElement.id.split('-')[1];
            editors[editorElement.id] = initializeEditor(editorElement, languageId);
            setupFileUploadListener(languageId);
        }
    }
    
    function initializeEditor(element, languageId) {
        return new SimpleMDE({
            element: element,
            spellChecker: false,
            autosave: {
                enabled: false,
                delay: 1000,
                uniqueId: element.id
            },
            toolbar: [
                "bold", "italic", "heading", "|", 
                "quote", "unordered-list", "ordered-list", "|",
                {
                    name: "custom-image",
                    action: function(editor) {
                        document.getElementById(`image-upload-${languageId}`).click();
                    },
                    className: "fa fa-picture-o",
                    title: "Upload Image",
                },
                "link", "preview", "side-by-side", "fullscreen"
            ]
        });
    }
    
    // Listen for tab show events
    const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabElements.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('href').substring(1);
            const targetPane = document.getElementById(targetId);
            const editorElement = targetPane.querySelector('.markdown-editor');
            
            if (editorElement) {
                const languageId = editorElement.id.split('-')[1];
                
                if (editors[editorElement.id]) {
                    // If editor exists, refresh it
                    editors[editorElement.id].codemirror.refresh();
                } else {
                    // If editor doesn't exist yet, initialize it
                    editors[editorElement.id] = initializeEditor(editorElement, languageId);
                    setupFileUploadListener(languageId);
                }
            }
        });
    });

    function setupFileUploadListener(languageId) {
        document.getElementById(`image-upload-${languageId}`).addEventListener('change', function(e) {
            e.stopPropagation(); 

            const file = this.files[0];
            if (!file) return;
            
            const editorId = `content-${languageId}`;
            const editor = editors[editorId];
            
            // Show loading indicator in editor
            const cm = editor.codemirror;
            const cursor = cm.getCursor();
            cm.replaceRange('![Uploading image...]()', cursor);
            
            // Create form data for upload
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            // Send to your server
            fetch('{{ route('admin.files.upload') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Replace loading text with actual image markdown
                    const text = cm.getValue();
                    const newText = text.replace(
                        '![Uploading image...]()', 
                        `![${file.name}](${data.data.file.url})`
                    );
                    cm.setValue(newText);
                } else {
                    throw new Error('Upload failed');
                }
            })
            .catch(error => {
                // Handle error - replace loading text with error message
                const text = cm.getValue();
                cm.setValue(text.replace('![Uploading image...]()', '![Upload failed]()')); 
                console.error('Upload failed:', error);
            });
            
            // Clear the input so the same file can be selected again
            this.value = '';
        });
    }

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
            const contentId = `content-${languageId}`;
            const submitButton = this.querySelector('.translation-submit');
            
            // Reset validation state
            titleInput.classList.remove('is-invalid');
            descriptionTextarea.classList.remove('is-invalid');
            document.getElementById(`error-title-${languageId}`).textContent = '';
            document.getElementById(`error-description-${languageId}`).textContent = '';
            document.getElementById(`error-content-${languageId}`).textContent = '';
            
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
            formData.append('content', editors[contentId].value());
            
            // Send AJAX request
            fetch('{{ route('admin.grades.update-translation', $grade->id) }}', {
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
                    
                    if (error.errors.content) {
                        document.getElementById(`error-content-${languageId}`).textContent = error.errors.content[0];
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
