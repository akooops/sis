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
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.menu-items.store', ['menu' => $menu->id]) }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Menu Item name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name')}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger"><strong>{{$message}}</strong></p>
                                    @enderror
                                </div>  

                                <div class="mb-3">
                                    <label class="form-label">Where should this menu item link?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_no" value="0" {{ old('external', '0') == '0' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="external_no">
                                                Link to a page or model
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_yes" value="1" {{ old('external') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="external_yes">
                                                Redirect to a URL
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- External URL Section -->
                                <div id="is-external" class="mb-3" style="display: none;">
                                    <label class="form-label">External URL</label>
                                    <input name="url" value="{{old('url')}}" type="url" class="form-control" placeholder="https://example.com">
                                    @error('url')
                                        <p class="mx-2 my-2 text-danger"><strong>{{$message}}</strong></p>
                                    @enderror
                                </div>

                                <!-- Internal Link Section -->
                                <div id="isnot-external" class="mb-3">
                                    <div class="mb-3">
                                        <label class="form-label">Select Content Type</label>
                                        <select name="linkable_type" id="linkable_type" class="form-select">
                                            <option value="">-- Select Type --</option>
                                            <option value="App\Models\Page" {{ old('linkable_type') == 'App\Models\Page' ? 'selected' : '' }}>Page</option>
                                            <option value="App\Models\Program" {{ old('linkable_type') == 'App\Models\Program' ? 'selected' : '' }}>Program</option>
                                            <option value="App\Models\Article" {{ old('linkable_type') == 'App\Models\Article' ? 'selected' : '' }}>Article</option>
                                            <option value="App\Models\Album" {{ old('linkable_type') == 'App\Models\Album' ? 'selected' : '' }}>Album</option>
                                            <option value="App\Models\Event" {{ old('linkable_type') == 'App\Models\Event' ? 'selected' : '' }}>Event</option>
                                            <option value="App\Models\Grade" {{ old('linkable_type') == 'App\Models\Grade' ? 'selected' : '' }}>Grade</option>
                                            <option value="App\Models\JobPosting" {{ old('linkable_type') == 'App\Models\JobPosting' ? 'selected' : '' }}>Job</option>
                                        </select>
                                        @error('linkable_type')
                                            <p class="mx-2 my-2 text-danger"><strong>{{$message}}</strong></p>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Select Item</label>
                                        <select name="linkable_id" id="linkable_id" class="form-select">
                                            <option value="">-- Select Type First --</option>
                                        </select>
                                        @error('linkable_id')
                                            <p class="mx-2 my-2 text-danger"><strong>{{$message}}</strong></p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Menu Item {{$defaultLanguage->name}} title <span class="text-danger">*</span></label>
                                    <input name="title" value="{{old('title')}}" type="text" class="form-control">
                                    @error('title')
                                        <p class="mx-2 my-2 text-danger"><strong>{{$message}}</strong></p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end mb-3">
                            <a href="{{ route('admin.menu-items.index', ['menu' => $menu->id]) }}" class="btn btn-primary w-sm">Back</a>
                            <button type="submit" class="btn btn-success w-sm">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const linkableItems = @json($linkableItems);

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

    function populateLinkableItems(type) {
        const linkableIdSelect = document.getElementById('linkable_id');
        linkableIdSelect.innerHTML = '<option value="">-- Select Item --</option>';

        if (type) {
            const typeName = type.split('\\').pop(); // Get class name from full namespace
            const items = linkableItems[typeName] || [];
            
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                
                // Restore old value if exists
                if ('{{ old("linkable_id") }}' == item.id) {
                    option.selected = true;
                }
                
                linkableIdSelect.appendChild(option);
            });
        }
    }

    // Bind change event to radios
    document.querySelectorAll('input[name="external"]').forEach(function(radio) {
        radio.addEventListener('change', toggleExternal);
    });

    // Bind change event to linkable_type select
    document.getElementById('linkable_type').addEventListener('change', function() {
        populateLinkableItems(this.value);
    });

    // Initial state
    toggleExternal();
    
    // Populate items if type is already selected (for old input)
    const selectedType = document.getElementById('linkable_type').value;
    if (selectedType) {
        populateLinkableItems(selectedType);
    }
});
</script>
@endsection
