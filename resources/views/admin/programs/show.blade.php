@extends('admin.layouts.master')
@section('title') Programs @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Programs @endslot
@slot('title') Show Program @endslot
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
                            <h4 class="card-title mb-0">Program information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$program->thumbnailUrl}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Program name</h4>
                                {{$program->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Program slug</h4>
                                <span class="badge bg-primary"> {{$program->slug }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Program created at</h4>
                                {{$program->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Program updated at</h4>
                                {{$program->updated_at}}
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
                                            <h4 class="fs-15">Program {{$language->name}} title</h4>
                                            {{$program->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Program {{$language->name}} subtitle</h4>
                                            {{$program->getTranslation('subtitle', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Program {{$language->name}} description</h4>
                                            {{$program->getTranslation('description', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Program {{$language->name}} content</h4>
                                            <x-markdown>
                                                {{ $program->getTranslation('content', $language->code) }}
                                            </x-markdown>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.programs.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.programs.update')
                            <a href="{{ route('admin.programs.edit', $program->id) }}" class="btn btn-success w-sm">Edit</a>
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
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
