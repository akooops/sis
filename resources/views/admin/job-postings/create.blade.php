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
</style>
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Job Postings @endslot
@slot('title') Create Job Posting @endslot
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

            <form id="main-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.job-postings.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Job name<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Job slug<span class="text-danger">*</span></label>
                                    <input name="slug" value="{{old('slug')}}" type="text" class="form-control">
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
                                                <option value="full_time" {{ old('employment_type') == 'full_time' ? 'selected' : '' }}>Full Time</option>
                                                <option value="part_time" {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>Part Time</option>
                                                <option value="internship" {{ old('employment_type') == 'internship' ? 'selected' : '' }}>Internship</option>
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
                                            <input name="number_of_positions" value="{{old('number_of_positions', 1)}}" type="number" min="1" class="form-control">
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
                                        <input class="form-check-input" type="checkbox" name="is_remote" value="1" id="is_remote" {{ old('is_remote') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_remote">
                                            Remote Work Available
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label" for="">Required Years of Experience</label>
                                            <input name="required_years_of_experience" value="{{old('required_years_of_experience')}}" type="number" min="0" max="50" class="form-control">
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
                                            <input name="application_deadline" value="{{old('application_deadline')}}" type="text" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" readonly="readonly">
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
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Job {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Job {{$defaultLanguage->name}} description <span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Required Skills {{$defaultLanguage->name}}</label>
                                    <div class="tags-input" id="skills-tags-input">
                                        <input type="text" class="tag-input">
                                    </div>
                                    <input type="hidden" name="required_skills" id="skills-hidden-input" value="{{old('required_skills')}}">
                                    <small class="text-muted">Type skills and press Enter or comma to add them</small>
                                    @error('required_skills')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                                
                                <div class="mb-4">
                                    <input type="file" id="image-upload" accept="image/*" style="display: none;">

                                    <label class="form-label" for="">Job {{$defaultLanguage->name}} content <span class="text-danger">*</span></label>
                                    <textarea id="markdown-editor" name="content" type="text" class="form-control">{{old('content')}}</textarea>
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
                            <a href="{{ route('admin.job-postings.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize SimpleMDE for content
    const simplemde = new SimpleMDE({ 
        element: document.getElementById("markdown-editor"),
        spellChecker: false,
        autosave: {
            enabled: false,
            delay: 1000,
        },
        toolbar: [
            "bold", "italic", "heading", "|", 
            "quote", "unordered-list", "ordered-list", "|",
            {
                name: "custom-image",
                action: function customFunction(editor){
                    document.getElementById('image-upload').click();
                },
                className: "fa fa-picture-o",
                title: "Upload Image",
            },
            "link", "preview", "side-by-side", "fullscreen"
        ]
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

    // Handle image upload for markdown editor
    document.getElementById('image-upload').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        // Show loading indicator in editor
        const cm = simplemde.codemirror;
        const cursor = cm.getCursor();
        cm.replaceRange('![Uploading image...]()', cursor);
        
        // Create form data for upload
        const formData = new FormData();
        formData.append('file', file);
        
        // Send to your server
        fetch('{{ route('admin.files.upload') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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

    // Skills Tags Input Functionality
    const tagsContainer = document.getElementById('skills-tags-input');
    const tagInput = tagsContainer.querySelector('.tag-input');
    const hiddenInput = document.getElementById('skills-hidden-input');
    let skills = [];

    // Load existing skills from old input
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
                <button type="button" class="tag-remove" onclick="removeSkill(${index})">×</button>
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

    window.removeSkill = function(index) {
        skills.splice(index, 1);
        renderTags();
    };

    // Handle input events
    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                // Handle multiple skills separated by commas
                const newSkills = value.split(',').map(s => s.trim()).filter(s => s);
                newSkills.forEach(skill => addSkill(skill));
                this.value = '';
            }
        } else if (e.key === 'Backspace' && this.value === '' && skills.length > 0) {
            // Remove last skill when backspace is pressed on empty input
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
});
</script>
@endsection
