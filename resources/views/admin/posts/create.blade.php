@extends('admin.layouts.master')
@section('title') Posts @endsection
@section('css')
<link href="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css"><link href="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />

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
@slot('li_1') Posts @endslot
@slot('title') Create Post @endslot
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('posts.store') }}">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <!-- Danger Alert -->
                                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-error-warning-line me-3 align-middle"></i> Fields with * sign are mandatory.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>


                                <input name="tmp" value="{{$tmp}}" type="hidden">

                                <div class="mb-3">
                                    <label for="role" class="form-label">Post Category <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select">
                                        <option></option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>

                                    @error('category_id')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Post Title <span class="text-danger">*</span></label>
                                    <input name="title" value="{{old('title')}}" type="text" class="form-control">
                                    @error('title')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Post Overview <span class="text-danger">*</span></label>
                                    <textarea name="overview" type="text" class="form-control">{{old('overview')}}</textarea>
                                    @error('overview')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Post Image <span class="text-danger">*</span></label>
                                    <input name="file" type="file" class="form-control" accept="image/*">
                                    @error('file')
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

                        <div class="card">
                            <div class="card-body">
                                <div class="spinner-border text-primary" id="uploadLoading" role="status" style="display: none">
                                    <span class="visually-hidden">Uploading...</span>
                                </div>

                                <div class="mb-3">
                                    <label>Post Content <span class="text-danger">*</span></label>
                                    <textarea name="content" id="content">{{old('content')}}</textarea>
                                    @error('content')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror

                                    <input id="simpleMDEImageUploadInput" type="file" accept="image/*" style="display: none;">
                                </div>

                                <div id="sources" class="mb-3">
                                    <label>Post Sources</label>

                                    <input name="sources" v-model="sources_text" type="hidden">

                                    <div v-for="(source, index) in sources" class="row">
                                        <div class="mb-3 col-12">
                                            <div class="input-group">
                                                <input v-model="source.content" v-on:input="generateSourcesText" type="text" class="form-control" required>
                                                <button v-on:click="remove(index)" class="btn btn-danger" type="button">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
    
                                    <div class="mb-4">
                                        <button v-on:click="add" type="button" class="btn btn-danger">Add Source</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-3">
                            <button type="submit" class="btn btn-success w-sm">Submit</button>
                        </div>
                    </div>
                    <!-- end col -->

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label" for="">Post Reading time in minutes <span class="text-danger">*</span></label>
                                    <input name="reading_time" value="{{old('reading_time')}}" type="number" min="0" class="form-control">
                                    @error('reading_time')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                            </div>
                            <!-- end card body -->
                        </div> 
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-info alert-border-left" role="alert">
                                    <i class="ri-airplay-line me-3 align-middle"></i> If you want to post right away let it blank -For now will work like this-
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Schedule Publish</label>
                                    <input name="published" value="{{old('published')}}" type="text" id="datepicker-from-input" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" data-time_24hr="true" data-enable-time="true" readonly="readonly">
                                    @error('published')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                            </div>
                            <!-- end card body -->
                        </div> 
                    </div>
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
<script src="{{ URL::asset('assets/admin/libs/vue/vue.js') }}"></script>

<script>
    $(document).ready(function() {
        const input = $("#simpleMDEImageUploadInput");
        const uploadLoading = $("#uploadLoading");

        const simplemde = new SimpleMDE({
            element: document.getElementById("content"),
            toolbar: [
                "bold", "italic", "heading", "|", 
                'quote', 'unordered-list', 'ordered-list', '|', 
                'link', '|', 
                'preview', 'side-by-side', 'fullscreen', '|',
                {
                    name: "upload-image",
                    action: function(editor) {                       
                        input.click();
                    },
                    className: "fa fa-picture-o",
                    title: "Insert Image",
                }
            ],
        });

        input.on("change", function() {
            const file = this.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('path', 'posts'); 
                formData.append('tmp', '{{ $tmp }}'); 
                formData.append('_token', "{{ csrf_token() }}"); 

                uploadLoading.show();

                $.ajax({
                    url: "{{ route('files.upload') }}",
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            const imageUrl = response.data.url;
                            const imageMarkdown = `![Alt text](${imageUrl})\n`;
                            simplemde.codemirror.replaceSelection(imageMarkdown);
                        } else {
                            console.error('Image upload failed.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Image upload error:', error);
                    },
                    complete: function() {
                        uploadLoading.hide();
                        input.val('');
                    }
                });
            }
        });
    });

    Sources = new Vue({
        el : "#sources",
        data : {
            sources : [],
            sources_text: ''
        },
        methods : {
            add: function(){
                let source = {
                    content: ''
                };

                this.sources.push(source);
                this.generateSourcesText();
            },
            remove: function(sourceIndex){
                this.sources.splice(sourceIndex, 1);
                this.generateSourcesText();
            },
            generateSourcesText: function(){
                this.sources_text = '';
                this.sources.forEach((source, index) => {
                    this.sources_text += source.content;
                    if(index != this.sources.length - 1)
                        this.sources_text += ';;;';
                });
            }
        }
    })
</script>
@endsection
