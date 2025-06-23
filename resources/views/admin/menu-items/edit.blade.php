@extends('admin.layouts.master')
@section('title') Menu Items @endsection
@section('css')
<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .translation-error {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    .translation-success {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: none;
    }
</style>
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Menu Items @endslot
@slot('title') Edit Menu Item @endslot
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

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.menu-items.update', $menuItem->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Menu Item name<span class="text-danger">*</span></label>
                                    <input name="name" value="{{old('name', $menuItem->name)}}" type="text" class="form-control">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>{{$message}}</strong>
                                        </p>
                                    @enderror
                                </div>  

                                <div class="mb-3">
                                    <label class="form-label">Where should this menu item link?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_no" value="0" 
                                                {{ old('external', $menuItem->linkable_id ? '0' : ($menuItem->url ? '1' : '0')) == '0' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="external_no">
                                                Link to a page or model
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="external" id="external_yes" value="1" 
                                                {{ old('external', $menuItem->linkable_id ? '0' : ($menuItem->url ? '1' : '0')) == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="external_yes">
                                                Redirect to a URL
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- External URL Section -->
                                <div id="is-external" class="mb-3" style="display: none;">
                                    <label class="form-label">External URL</label>
                                    <input name="url" value="{{old('url', $menuItem->url)}}" class="form-control" placeholder="https://example.com">
                                    @error('url')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>{{$message}}</strong>
                                        </p>
                                    @enderror
                                </div>

                                <!-- Internal Link Section -->
                                <div id="isnot-external" class="mb-3" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Select Content Type</label>
                                        <select name="linkable_type" id="linkable_type" class="form-select">
                                            <option value="">-- Select Type --</option>
                                            <option value="App\Models\Page" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Page' ? 'selected' : '' }}>Page</option>
                                            <option value="App\Models\Program" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Program' ? 'selected' : '' }}>Program</option>
                                            <option value="App\Models\Article" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Article' ? 'selected' : '' }}>Article</option>
                                            <option value="App\Models\Album" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Album' ? 'selected' : '' }}>Album</option>
                                            <option value="App\Models\Event" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Event' ? 'selected' : '' }}>Event</option>
                                            <option value="App\Models\Grade" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\Grade' ? 'selected' : '' }}>Grade</option>
                                            <option value="App\Models\JobPosting" {{ old('linkable_type', $menuItem->linkable_type) == 'App\Models\JobPosting' ? 'selected' : '' }}>Job</option>
                                        </select>
                                        @error('linkable_type')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>{{$message}}</strong>
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Select Item</label>
                                        <select name="linkable_id" id="linkable_id" class="form-select">
                                            <option value="">-- Select Type First --</option>
                                        </select>
                                        @error('linkable_id')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>{{$message}}</strong>
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Update</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Success alert for AJAX responses -->
                    <div class="alert alert-success alert-dismissible fade translation-success" role="alert" id="translationSuccess">
                        <strong>Success!</strong> <span id="successMessage">Translation updated successfully.</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Translation Section -->
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

                            <div class="tab-content">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <form class="translation-form" data-language="{{$language->code}}" data-language-id="{{$language->id}}">
                                            @csrf
                                            <input type="hidden" name="language_id" value="{{$language->id}}">

                                            <div class="mb-4">
                                                <label class="form-label" for="title-{{$language->id}}">Title <span class="text-danger">*</span></label>
                                                <input type="text" id="title-{{$language->id}}" name="title" class="form-control translation-title" value="{{$menuItem->getTranslation('title', $language->code)}}">
                                                <div class="translation-error" id="error-title-{{$language->id}}"></div>
                                            </div>

                                            <div class="text-end mt-3">
                                                <button type="submit" class="btn btn-success w-sm translation-submit">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mb-3">
                        <a href="{{ route('admin.menu-items.index', ['menu' => $menuItem->menu_id]) }}" class="btn btn-primary w-sm">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const linkableItems = @json($linkableItems);
    const currentLinkableId = {{ $menuItem->linkable_id ?? 'null' }};
    const currentLinkableType = @json($menuItem->linkable_type ?? '');

    function toggleExternal() {
        let isExternal = document.querySelector('input[name="external"]:checked').value;
        if (isExternal === "1") {
            document.getElementById('is-external').style.display = '';
            document.getElementById('isnot-external').style.display = 'none';
        } else {
            document.getElementById('is-external').style.display = 'none';
            document.getElementById('isnot-external').style.display = '';
        }
    }

    function populateLinkableItems(type, selectedId = null) {
        const linkableIdSelect = document.getElementById('linkable_id');
        linkableIdSelect.innerHTML = '<option value="">-- Select Item --</option>';

        if (type) {
            const typeName = type.split('\\').pop();
            const items = linkableItems[typeName] || [];
            
            console.log('Populating items for type:', typeName, 'Items:', items);
            
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                
                if (selectedId && selectedId == item.id) {
                    option.selected = true;
                    console.log('✓ Selected item:', item.name, 'ID:', item.id);
                }
                
                linkableIdSelect.appendChild(option);
            });
        }
    }

    function initializePageState() {
        console.log('=== INITIALIZING PAGE STATE ===');
        console.log('Current linkable type:', currentLinkableType);
        console.log('Current linkable ID:', currentLinkableId);
        
        // Set radio buttons and toggle sections
        toggleExternal();
        
        // Set the linkable type dropdown if we have a current type
        if (currentLinkableType) {
            const linkableTypeSelect = document.getElementById('linkable_type');
            console.log('Setting linkable type select to:', currentLinkableType);
            
            // Set the value
            linkableTypeSelect.value = currentLinkableType;
            
            // Verify it was set
            console.log('Linkable type select value after setting:', linkableTypeSelect.value);
            console.log('Available options:', Array.from(linkableTypeSelect.options).map(opt => opt.value));
            
            // If it wasn't set, try to find the option manually
            if (linkableTypeSelect.value !== currentLinkableType) {
                console.warn('Direct value setting failed, trying to find option manually');
                Array.from(linkableTypeSelect.options).forEach(option => {
                    if (option.value === currentLinkableType) {
                        option.selected = true;
                        console.log('✓ Manually selected option:', option.value);
                    }
                });
            }
            
            // Now populate the items dropdown
            if (currentLinkableId) {
                console.log('Populating linkable items...');
                populateLinkableItems(currentLinkableType, currentLinkableId);
            }
        }
    }

    // Bind change event to radios
    document.querySelectorAll('input[name="external"]').forEach(function(radio) {
        radio.addEventListener('change', toggleExternal);
    });

    // Bind change event to linkable_type select
    document.getElementById('linkable_type').addEventListener('change', function() {
        console.log('Linkable type changed to:', this.value);
        populateLinkableItems(this.value);
    });

    // Initialize with delay to ensure DOM is fully ready
    setTimeout(function() {
        initializePageState();
    }, 200);

    // Handle translation form submissions (unchanged)
    const forms = document.querySelectorAll('.translation-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const languageId = this.dataset.languageId;
            const languageCode = this.dataset.language;
            const titleInput = document.getElementById(`title-${languageId}`);
            const submitButton = this.querySelector('.translation-submit');
            
            // Reset validation state
            titleInput.classList.remove('is-invalid');
            document.getElementById(`error-title-${languageId}`).textContent = '';

            // Show loading spinner on button
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            submitButton.disabled = true;
            
            // Get form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('_method', 'PATCH');
            formData.append('language_id', languageId);
            formData.append('title', titleInput.value);
            
            // Send AJAX request
            fetch('{{ route('admin.menu-items.update-translation', $menuItem->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw data;
                    });
                }
                return response.json();
            })
            .then(data => {
                // Show success message
                const successAlert = document.getElementById('translationSuccess');
                document.getElementById('successMessage').textContent = `Translation for ${languageCode} updated successfully.`;
                successAlert.classList.add('show');
                
                // Hide after 3 seconds
                setTimeout(() => {
                    successAlert.classList.remove('show');
                }, 3000);
            })
            .catch(error => {
                // Handle validation errors
                if (error.errors) {
                    if (error.errors.title) {
                        titleInput.classList.add('is-invalid');
                        document.getElementById(`error-title-${languageId}`).textContent = error.errors.title[0];
                    }
                } else {
                    // General error
                    document.getElementById(`error-title-${languageId}`).textContent = 'An error occurred. Please try again.';
                }
            })
            .finally(() => {
                // Restore button state
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            });
        });
    });
});
</script>
@endsection
