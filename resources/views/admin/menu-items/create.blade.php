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
                                    <label class="form-label" for="">Does this page redirect to a url?</label>

                                    <select id="external" onchange="change()" name="external" class="form-select mb-3">
                                        <option value="0" selected>No - It's linked with a page</option>
                                        <option value="1">Yes - It will redirect to a url</option>
                                    </select>         
                                    
                                    @error('external')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div id="is-external" class="mb-3">
                                    <label class="form-label" for="">External url</label>
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
                                    <label class="form-label" for="">Menu Item Linked Page -Choose a page-</label>

                                    <select name="page_id" class="form-select mb-3">
                                        <option value="" selected>This section has no page</option>
                                        @foreach($pages as $page)
                                            <option value="{{ $page->id }}">#{{ $page->id }} - {{ $page->title }}</option>
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
    function change(){
        let isExternal = $('#external').find(":selected").val();

        if(isExternal == true){
            $('#is-external').show();
            $('#isnot-external').hide();
        }else{
            $('#is-external').hide();
            $('#isnot-external').show();
        }
    }

    change();
});
</script>
@endsection
