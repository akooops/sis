@extends('admin.layouts.master')
@section('title') Settings @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Settings @endslot
@slot('title') Edit Setting @endslot
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

            <form method="POST" action="{{ route('admin.settings.update', $setting->id) }}">
                @csrf
                @method('patch')
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-info">
                                    {{$setting->description}}
                                </div>

                                @if ($setting->type == 'text')
                                    <div class="mb-4">
                                        <label class="form-label" for="">Value <span class="text-danger">*</span></label>
                                        <input name="value" value="{{ $setting->value }}" type="text" class="form-control">
                                        @error('value')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>
                                @endif

                                @if ($setting->type == 'menu')
                                    <div class="mb-4">
                                        <label class="form-label" for="">Value<span class="text-danger">*</span></label>
                                        <select class="form-control" name="value">
                                            @foreach($menus as $menu)
                                                <option value="{{ $menu->id }}" {{ $setting->value == $menu->id ? 'selected' : '' }}>
                                                    {{ $menu->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('value')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>  
                                @endif

                                @if ($setting->type == 'page')
                                    <div class="mb-4">
                                        <label class="form-label" for="">Value<span class="text-danger">*</span></label>
                                        <select class="form-control" name="value">
                                            @foreach($pages as $page)
                                                <option value="{{ $page->id }}" {{ $setting->value == $page->id ? 'selected' : '' }}>
                                                    {{ $page->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('value')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                        @enderror
                                    </div>  
                                @endif
                            </div>
                        </div>

                        <div class="text-end mb-3">
                            <a href="{{ route('admin.settings.index') }}" class="btn btn-primary w-sm">Back</a>
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
@endsection
