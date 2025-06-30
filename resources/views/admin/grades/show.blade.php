@extends('admin.layouts.master')
@section('title') Grades @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Grades @endslot
@slot('title') Show Grade @endslot
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
                            <h4 class="card-title mb-0">Grade information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$grade->thumbnailUrl}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Grade name</h4>
                                {{$grade->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Grade slug</h4>
                                <span class="badge bg-primary"> {{$grade->slug }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Grade program</h4>
                                {{$grade->program->name }}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Grade created at</h4>
                                {{$grade->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Grade updated at</h4>
                                {{$grade->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Grade files</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <ul class="list-unstyled mb-0">
                                    @foreach ($grade->files as $key => $file)
                                        <li id="file-{{$key}}" class="mt-2">
                                            <div class="border rounded">
                                                <div class="d-flex p-2">
                                                    <div class="flex-grow-1">
                                                        <div class="pt-1">
                                                            <h5 class="fs-14 mb-1">
                                                                <a href="{{$file->url}}" target="_blank"> {{$file->original_name}} </a>
                                                            </h5>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-3">
                                                        <a href="{{$file->url}}" class="btn btn-sm btn-info show-item-btn" target="_blank"> Download </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

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
                                            <h4 class="fs-15">Grade {{$language->name}} title</h4>
                                            {{$grade->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Grade {{$language->name}} description</h4>
                                            {{$grade->getTranslation('description', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Grade {{$language->name}} content</h4>
                                            {!! $grade->getTranslation('content', $language->code) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.grades.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.grades.update')
                            <a href="{{ route('admin.grades.edit', $grade->id) }}" class="btn btn-success w-sm">Edit</a>
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
