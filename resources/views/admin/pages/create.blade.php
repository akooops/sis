@extends('admin.layouts.master')
@section('title') Pages @endsection
@section('css')
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
</style>
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Pages @endslot
@slot('title') Create Page @endslot
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

            <form method="POST" action="{{ route('admin.pages.store') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Page name<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Page slug<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Page name<span class="text-danger">*</span></label>
                                    <select class="form-control" name="status">
                                        <option value="draft">Draft</option>
                                        <option value="hidden">Hidden</option>
                                        <option value="published">Published</option>
                                    </select>

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

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Page {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Page {{$defaultLanguage->name}} description <span class="text-danger">*</span></label>
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
                                    <input type="file" id="image-upload" accept="image/*" style="display: none;">

                                    <label class="form-label" for="">Page {{$defaultLanguage->name}} content <span class="text-danger">*</span></label>
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
                            <a href="{{ route('admin.pages.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Handle image upload
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
});
</script>
@endsection
