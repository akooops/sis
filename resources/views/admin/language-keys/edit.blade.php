@extends('admin.layouts.master')
@section('title') Language Keys @endsection
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
@slot('li_1') Language Keys @endslot
@slot('title') Edit Language Key @endslot
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

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <!-- Success alert for AJAX responses -->
            <div class="alert alert-success alert-dismissible fade translation-success" role="alert" id="translationSuccess">
                <strong>Success!</strong> <span id="successMessage">Translation updated successfully.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="row">
                <div class="col-12">
                    <form method="POST" action="{{ route('admin.language-keys.update', $languageKey->id) }}">
                        @csrf
                        @method('patch')
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Language Key <span class="text-danger">*</span></label>
                                    <input name="key" value="{{$languageKey->key}}" type="text" class="form-control">
                                    @error('key')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>    
                                
                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Success alert for AJAX responses -->
                    <div class="alert alert-success alert-dismissible fade translation-success" role="alert" id="translationSuccess">
                        <strong>Success!</strong> <span id="successMessage">Translation updated successfully.</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

                            <div class="tab-content">
                                @foreach ($languages as $key => $language)
                                    <div class="tab-pane {{$key == 0 ? "active" : ""}}" id="{{$language->code}}" role="tabpanel">
                                        <form class="translation-form" data-language="{{$language->code}}" data-language-id="{{$language->id}}">
                                            @csrf
                                            <input type="hidden" name="language_id" value="{{$language->id}}">

                                            <label class="form-label" for="content-{{$language->id}}">Language key {{$language->name}} content</label>
                                            <textarea id="content-{{$language->id}}" name="content" class="form-control translation-content">{{$languageKey->getTranslation('content', $language->code)}}</textarea>
                                            <div class="translation-error" id="error-content-{{$language->id}}"></div>

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
                        <a href="{{ route('admin.language-keys.index') }}" class="btn btn-primary w-sm">Back</a>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.translation-form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const languageId = this.dataset.languageId;
                const languageCode = this.dataset.language;
                const contentTextarea = document.getElementById(`content-${languageId}`);
                const errorElement = document.getElementById(`error-content-${languageId}`);
                const submitButton = this.querySelector('.translation-submit');
                
                // Reset validation state
                contentTextarea.classList.remove('is-invalid');
                errorElement.textContent = '';
                
                // Show loading spinner on button
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                submitButton.disabled = true;
                
                // Get form data
                const formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                formData.append('_method', 'PATCH');
                formData.append('language_id', languageId);
                formData.append('content', contentTextarea.value);
                
                // Send AJAX request
                fetch('{{ route('admin.language-keys.update-translation', $languageKey->id) }}', {
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
                    if (error.errors && error.errors.content) {
                        contentTextarea.classList.add('is-invalid');
                        errorElement.textContent = error.errors.content[0];
                    } else {
                        // General error
                        contentTextarea.classList.add('is-invalid');
                        errorElement.textContent = 'An error occurred. Please try again.';
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
