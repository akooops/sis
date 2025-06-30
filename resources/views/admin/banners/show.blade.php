@extends('admin.layouts.master')
@section('title') Banners @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Banners @endslot
@slot('title') Show Banner @endslot
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
                            <h4 class="card-title mb-0">Banner information</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$banner->thumbnailUrl}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Banner name</h4>
                                {{$banner->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Banner page</h4>
                                @if($banner->page)
                                    <span class="badge bg-primary"> {{$banner->page->name }} </span>
                                @else
                                    <span class="badge bg-danger"> not linked</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Banner url</h4>
                                @if(!$banner->page)
                                    <a href="{{$banner->url}}" target="_blank" rel="noopener noreferrer" class="me-2">
                                        <span class="badge bg-primary">
                                            <i class="ri-link"></i> Open
                                        </span>
                                    </a>                                                
                                @else
                                    <span class="badge bg-danger"> linked</span>
                                @endif
                            </div>

                            @if($banner->video)
                                <div class="mb-3">
                                    <h4 class="fs-15">Attached video</h4>
                                    <video 
                                        preload="none" 
                                        controls
                                        width="300" 
                                        height="200">
                                        <source src="{{$banner->videoUrl}}">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif

                            <div class="mb-3">
                                <h4 class="fs-15">Banner created at</h4>
                                {{$banner->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Banner updated at</h4>
                                {{$banner->updated_at}}
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
                                            <h4 class="fs-15">Banner {{$language->name}} title</h4>
                                            {{$banner->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Banner {{$language->name}} cta</h4>
                                            {{$banner->getTranslation('cta', $language->code)}}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.banners.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.banners.update')
                            <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-success w-sm">Edit</a>
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
