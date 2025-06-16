@extends('admin.layouts.master')
@section('title') Job Postings @endsection
@section('css')
<link href="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .editor-toolbar.fullscreen{
        z-index: 9999999;
    }

    .CodeMirror-fullscreen{
        z-index: 9999999;
    }

    .editor-preview-side {
        z-index: 9999999;
    }

    .tags-input {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        min-height: 38px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
    }

    .tag {
        background-color: #0d6efd;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .tag-remove {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
    }

    .tag-input {
        border: none;
        outline: none;
        flex: 1;
        min-width: 100px;
    }

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
@slot('li_1') Job Postings @endslot
@slot('title') Edit Job Posting @endslot
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
                    <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.job-postings.update', $jobPosting->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Job name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{$jobPosting->name}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  
                                
                                <div class="mb-4">
                                    <label class="form-label" for="">Job slug<span class="text-danger">*</span></label>
                                    <input name="slug" value="{{$jobPosting->slug}}" type="text" class="form-control">
                                    @error('slug')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="">Employment Type<span class="text-danger">*</span></label>
                                            <select class="form-control" name="employment_type">
                                                <option value="full_time" {{ $jobPosting->employment_type == 'full_time' ? 'selected' : '' }}>Full Time</option>
                                                <option value="part_time" {{ $jobPosting->employment_type == 'part_time' ? 'selected' : '' }}>Part Time</option>
                                                <option value="internship" {{ $jobPosting->employment_type == 'internship' ? 'selected' : '' }}>Internship</option>
                                            </select>
                                            @error('employment_type')
                                                <p class="mx-2 my-2 text-danger">
                                                    <strong>
                                                        {{$message}}
                                                    </strong>
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="">Number of Positions</label>
                                            <input name="number_of_positions" value="{{$jobPosting->number_of_positions}}" type="number" min="1" class="form-control">
                                            @error('number_of_positions')
                                                <p class="mx-2 my-2 text-danger">
                                                    <strong>
                                                        {{$message}}
                                                    </strong>
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_remote" value="1" id="is_remote" {{ $jobPosting->is_remote ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_remote">
                                            Remote Work Available
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="">Required Years of Experience</label>
                                            <input name="required_years_of_experience" value="{{$jobPosting->required_years_of_experience}}" type="number" min="0" max="50" class="form-control">
                                            @error('required_years_of_experience')
                                                <p class="mx-2 my-2 text-danger">
                                                    <strong>
                                                        {{$message}}
                                                    </strong>
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="">Application Deadline</label>
                                            <input name="application_deadline" value="{{ $jobPosting->application_deadline}}" type="text" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" readonly="readonly">
                                            @error('application_deadline')
                                                <p class="mx-2 my-2 text-danger">
                                                    <strong>
                                                        {{$message}}
                                                    </strong>
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Job status<span class="text-danger">*</span></label>
                                    <select class="form-control" name="status">
                                        <option value="draft" {{ $jobPosting->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="hidden" {{ $jobPosting->status == 'hidden' ? 'selected' : '' }}>Hidden</option>
                                        <option value="published" {{ $jobPosting->status == 'published' ? 'selected' : '' }}>Published</option>
                                    </select>

                                    @error('status')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Update</button>
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

                            <!-- Tab panes -->
                            <div class="tab-content">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <form class="translation-form" data-language="{{$language->code}}" data-language-id="{{$language->id}}">
                                            @csrf
                                            <input type="hidden" name="language_id" value="{{$language->id}}">

                                            <div class="mb-4">
                                                <label class="form-label" for="title-{{$language->id}}">Title <span class="text-danger">*</span></label>
                                                <input type="text" id="title-{{$language->id}}" name="title" class="form-control translation-title" value="{{$jobPosting->getTranslation('title', $language->code)}}">
                                                <div class="translation-error" id="error-title-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="description-{{$language->id}}">Description <span class="text-danger">*</span></label>
                                                <textarea id="description-{{$language->id}}" name="description" class="form-control translation-description" rows="3">{{$jobPosting->getTranslation('description', $language->code)}}</textarea>
                                                <div class="translation-error" id="error-description-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="required_skills-{{$language->id}}">Required Skills</label>
                                                <div class="tags-input" id="skills-tags-input-{{$language->id}}">
                                                    <input type="text" class="tag-input">
                                                </div>
                                                <input type="hidden" name="required_skills" id="skills-hidden-input-{{$language->id}}" value="{{$jobPosting->getTranslation('required_skills', $language->code)}}">
                                                <small class="text-muted">Type skills and press Enter or comma to add them</small>
                                                <div class="translation-error" id="error-required_skills-{{$language->id}}"></div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label" for="content-{{$language->id}}">Content <span class="text-danger">*</span></label>
                                                <textarea id="content-{{$language->id}}" name="content" class="form-control translation-content markdown-editor">{{$jobPosting->getTranslation('content', $language->code)}}</textarea>
                                                <div class="translation-error" id="error-content-{{$language->id}}"></div>
                                            </div>

                                            <div class="text-end mt-3">
                                                <button type="submit" class="btn btn-success w-sm translation-submit">Update Translation</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.job-postings.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.js')}}"></script>
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
            
            // Initialize skills for the active tab
            initializeSkillsInput(languageId);
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
                    
                    // Initialize skills for this tab
                    initializeSkillsInput(languageId);
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

    // Skills functionality for each language tab
    function initializeSkillsInput(languageId) {
        const tagsContainer = document.getElementById(`skills-tags-input-${languageId}`);
        const tagInput = tagsContainer.querySelector('.tag-input');
        const hiddenInput = document.getElementById(`skills-hidden-input-${languageId}`);
        let skills = [];

        // Load existing skills from hidden input
        if (hiddenInput.value) {
            skills = hiddenInput.value.split(',').filter(skill => skill.trim() !== '');
            renderTags();
        }

        function renderTags() {
            // Remove existing tags
            tagsContainer.querySelectorAll('.tag').forEach(tag => tag.remove());
            
            // Add tags before input
            skills.forEach((skill, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `
                    ${skill}
                    <button type="button" class="tag-remove" onclick="removeSkill${languageId}(${index})">×</button>
                `;
                tagsContainer.insertBefore(tagElement, tagInput);
            });
            
            // Update hidden input
            hiddenInput.value = skills.join(',');
        }

        function addSkill(skillText) {
            const trimmedSkill = skillText.trim();
            if (trimmedSkill && !skills.includes(trimmedSkill)) {
                skills.push(trimmedSkill);
                renderTags();
            }
        }

        // Create global function for removing skills
        window[`removeSkill${languageId}`] = function(index) {
            skills.splice(index, 1);
            renderTags();
        };

        // Handle input events
        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    const newSkills = value.split(',').map(s => s.trim()).filter(s => s);
                    newSkills.forEach(skill => addSkill(skill));
                    this.value = '';
                }
            } else if (e.key === 'Backspace' && this.value === '' && skills.length > 0) {
                skills.pop();
                renderTags();
            }
        });

        // Handle paste events
        tagInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                const value = this.value.trim();
                if (value) {
                    const newSkills = value.split(',').map(s => s.trim()).filter(s => s);
                    newSkills.forEach(skill => addSkill(skill));
                    this.value = '';
                }
            }, 10);
        });

        // Handle focus out
        tagInput.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value) {
                const newSkills = value.split(',').map(s => s.trim()).filter(s => s);
                newSkills.forEach(skill => addSkill(skill));
                this.value = '';
            }
        });
    }

    // Handle translation form submissions
    const forms = document.querySelectorAll('.translation-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const languageId = this.dataset.languageId;
            const languageCode = this.dataset.language;
            const titleInput = document.getElementById(`title-${languageId}`);
            const descriptionTextarea = document.getElementById(`description-${languageId}`);
            const skillsHiddenInput = document.getElementById(`skills-hidden-input-${languageId}`);
            const contentId = `content-${languageId}`;
            const submitButton = this.querySelector('.translation-submit');
            
            // Reset validation state
            titleInput.classList.remove('is-invalid');
            descriptionTextarea.classList.remove('is-invalid');
            document.getElementById(`error-title-${languageId}`).textContent = '';
            document.getElementById(`error-description-${languageId}`).textContent = '';
            document.getElementById(`error-content-${languageId}`).textContent = '';
            document.getElementById(`error-required_skills-${languageId}`).textContent = '';
            
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
            formData.append('required_skills', skillsHiddenInput.value);
            formData.append('content', editors[contentId].value());
            
            // Send AJAX request
            fetch('{{ route('admin.job-postings.update-translation', $jobPosting->id) }}', {
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
                    
                    if (error.errors.required_skills) {
                        document.getElementById(`error-required_skills-${languageId}`).textContent = error.errors.required_skills[0];
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
