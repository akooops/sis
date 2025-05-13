@extends('admin.layouts.master')
@section('title') Event Editions @endsection
@section('css')
<link href="{{ URL::asset('assets/admin/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/admin/libs/dropzone/dropzone.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Event Editions @endslot
@slot('title') Create Event Edition @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good Morning, {{Auth::user()->name}}</h4>
                        </div>
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <form method="POST" enctype="multipart/form-data" action="{{ route('eventEditions.store', $event->id) }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Name </label>
                                    <input name="name" value="{{old('name')}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>


                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Description</label>
                                    <textarea name="description" type="text" class="form-control">{{old('description')}}</textarea>
                                    @error('description')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Number Of Participants </label>
                                    <input name="participants" value="{{old('participants')}}" type="number" class="form-control">
                                    @error('participants')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Place </label>
                                    <input name="place" value="{{old('place')}}" type="text" class="form-control">
                                    @error('place')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Starts At</label>
                                    <input name="starts_at" value="{{old('starts_at')}}" type="text" id="datepicker-from-input" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d"readonly="readonly">
                                    @error('starts_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="">Event Edition Ends At</label>
                                    <input name="ends_at" value="{{old('ends_at')}}" type="text" id="datepicker-from-input" class="form-control flatpickr-input" data-provider="flatpickr" data-date-format="Y-m-d" readonly="readonly">
                                    @error('ends_at')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <input name="tmp" value="{{$tmp}}" type="hidden">
                            </div>
                        </div>
                        <!-- end card -->

                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Event Editions Album</h4>
                            </div>

                            <div class="card-body">
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
                        </div>

                        <div class="text-end mb-3">
                            <a href="{{ route('eventEditions.index', $event->id) }}" class="btn btn-primary w-sm">Back</a>
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

<script src="{{ URL::asset('assets/admin/libs/dropzone/dropzone.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/libs/filepond/filepond.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') }}"></script>
<script src="{{ URL::asset('assets/admin/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') }}"></script>

<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>

<script>
    var previewTemplate,
        dropzone,
        dropzonePreviewNode = document.querySelector("#dropzone-preview-list");
    (dropzonePreviewNode.id = ""),
        dropzonePreviewNode &&
            ((previewTemplate = dropzonePreviewNode.parentNode.innerHTML),
            dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode),
            (dropzone = new Dropzone(".dropzone", { url: '{{ route('files.upload') }}', method: "post", headers: {'x-csrf-token': '{{csrf_token()}}'}, acceptedFiles: "image/*", previewTemplate: previewTemplate, previewsContainer: "#dropzone-preview" }))),
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

        dropzone.on('sending', function(file, xhr, formData){
            formData.append('tmp', '{{$tmp}}');
            formData.append('path', 'eventEditions');
        });

        dropzone.on('uploadprogress', function(file, progress, bytesSent){
            if (file.previewElement) 
                file.previewElement.querySelector("[data-dz-uploadprogress]").setAttribute('area-valuenow', Math.round(progress) + "%");
        });
</script>
@endsection