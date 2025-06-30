@extends('admin.layouts.master')
@section('title') Settings @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Settings @endslot
@slot('title') Show Setting @endslot
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
                            <h4 class="card-title mb-0">Setting information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="fs-15">Setting group</h4>
                                {{$setting->group}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting type</h4>
                                {{$setting->type}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting key</h4>
                                {{$setting->key}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting description</h4>
                                {{$setting->description}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting value</h4>
                                @if($setting->type == 'text')
                                    {{$setting->value}}
                                @elseif($setting->type == 'menu')
                                    @php
                                        $menuValue = \App\Models\Menu::find($setting->value);
                                    @endphp
                                    {{$menuValue->name}}
                                @elseif($setting->type == 'page')
                                    @php
                                        $pageValue = \App\Models\Page::find($setting->value);
                                    @endphp
                                    {{$pageValue->name}}
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting is encrypted?</h4>
                                @if($setting->is_encrypted)
                                    <span class="badge bg-success">yes</span>                                                    
                                @else
                                    <span class="badge bg-danger">no</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting created at</h4>
                                {{$setting->created_at}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Setting updated at</h4>
                                {{$setting->updated_at}}
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-primary w-sm">Back</a>
                        @haspermission('admin.settings.update')
                            <a href="{{ route('admin.settings.edit', $setting->id) }}" class="btn btn-success w-sm">Edit</a>
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
