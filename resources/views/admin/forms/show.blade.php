@extends('admin.layouts.master')
@section('title') Forms @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Forms @endslot
@slot('title') Show Form @endslot
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
                            <h4 class="card-title mb-0">Form information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Form name</h4>
                                {{$form->name}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Form slug</h4>
                                <span class="badge bg-primary"> {{$form->slug }} </span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Form status</h4>
                                @if($form->status == 'draft')
                                    <span class="badge bg-info">Draft</span>   
                                @elseif($form->status == 'hidden')   
                                    <span class="badge bg-primary">Hidden</span>                                                 
                                @else
                                    <span class="badge bg-success">Published</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Form has captcha?</h4>
                                @if($form->has_captcha)
                                    <span class="badge bg-danger">Yes</span>                                                  
                                @else
                                    <span class="badge bg-success">No</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Form created at</h4>
                                {{$form->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Form updated at</h4>
                                {{$form->updated_at}}
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
                                            <h4 class="fs-15">Form {{$language->name}} title</h4>
                                            {{$form->getTranslation('title', $language->code)}}
                                        </div>

                                        <div class="mb-3">
                                            <h4 class="fs-15">Form {{$language->name}} description</h4>
                                            {{$form->getTranslation('description', $language->code)}}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.forms.index') }}" class="btn btn-primary w-sm">Back</a>
                        
                        @haspermission('admin.forms.update')
                            <a href="{{ route('admin.forms.edit', $form->id) }}" class="btn btn-success w-sm">Edit</a>
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
