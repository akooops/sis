@extends('admin.layouts.master')
@section('title') Media @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Media @endslot
@slot('title') Show Media @endslot
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

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Media information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Media name</h4>
                                {{$media->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Media Type</h4>
                                {{$media->type}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Media main</h4>
                                @if($media->is_main)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif  
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Media url</h4>
                                <a href="{{$media->file->url}}" target="_blank" rel="noopener noreferrer" class="me-2">
                                    <span class="badge bg-primary">
                                        <i class="ri-link"></i> Open
                                    </span>
                                </a>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Media created at</h4>
                                {{$media->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Media updated at</h4>
                                {{$media->updated_at}}
                            </div>
                        </div>
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

                            <div class="tab-content text-muted">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <div class="mb-3">
                                            <h4 class="fs-15">Media {{$language->name}} title</h4>
                                            {{$media->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Media {{$language->name}} description</h4>
                                            {{$media->getTranslation('description', $language->code)}}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.media.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.media.update')
                            <a href="{{ route('admin.media.edit', $media->id) }}" class="btn btn-success w-sm">Edit</a>
                        @endhaspermission
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
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>
@endsection
