@extends('admin.layouts.master')
@section('title') Menu Items @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Menu Items @endslot
@slot('title') Create Menu Item @endslot
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.menu-items.store', ['menu' => $menu->id]) }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Menu Item name<span class="text-danger">*</span></label>
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
                                    <label class="form-label" for="">Where should this menu item link?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_no" value="0" checked>
                                            <label class="form-check-label" for="external_no">
                                                Link to a page
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_yes" value="1">
                                            <label class="form-check-label" for="external_yes">
                                                Redirect to a URL
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div id="is-external" class="mb-3">
                                    <input name="url" value="{{old('url')}}" type="text" class="form-control">
                                    @error('url')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div id="isnot-external" class="mb-3">
                                    <select name="page_id" class="form-select mb-3">
                                        @foreach($pages as $page)
                                            <option value="{{ $page->id }}">{{ $page->name }}</option>
                                        @endforeach
                                    </select>         
                                    
                                    @error('page_id')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>
                                
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Menu Item {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
                                    <input name="title" value="{{old('title')}}" type="text" class="form-control">
                                    @error('title')
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
                        
                        <div class="text-end mb-3">
                            <a href="{{ route('admin.menu-items.index', ['menu' => $menu->id]) }}" class="btn btn-primary w-sm">Back</a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleExternal() {
        let isExternal = document.querySelector('input[name="external"]:checked').value;
        if(isExternal === "1") {
            document.getElementById('is-external').style.display = '';
            document.getElementById('isnot-external').style.display = 'none';
        } else {
            document.getElementById('is-external').style.display = 'none';
            document.getElementById('isnot-external').style.display = '';
        }
    }

    // Bind change event to both radios
    document.querySelectorAll('input[name="external"]').forEach(function(radio) {
        radio.addEventListener('change', toggleExternal);
    });

    // Initial state
    toggleExternal();
});

</script>
@endsection
