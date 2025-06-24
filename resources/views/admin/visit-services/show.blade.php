@extends('admin.layouts.master')
@section('title') Visit Services @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Visit Services @endslot
@slot('title') Show Visit Service @endslot
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
                            <h4 class="card-title mb-0">Visit Service information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$visitService->thumbnailUrl}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Service name</h4>
                                {{$visitService->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Visit Service duration</h4>
                                <span class="badge bg-primary"> {{$visitService->formattedDuration }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">VisitService created at</h4>
                                {{$visitService->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">VisitService updated at</h4>
                                {{$visitService->updated_at}}
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
                                            <h4 class="fs-15">VisitService {{$language->name}} title</h4>
                                            {{$visitService->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">VisitService {{$language->name}} description</h4>
                                            {{$visitService->getTranslation('description', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">VisitService {{$language->name}} content</h4>
                                            {!! $visitService->getTranslation('content', $language->code) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.visit-services.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.visit-services.update')
                            <a href="{{ route('admin.visit-services.edit', $visitService->id) }}" class="btn btn-success w-sm">Edit</a>
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
