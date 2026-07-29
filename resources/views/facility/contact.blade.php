@extends('facility.layouts.master')
@section('title', getLanguageKeyLocalTranslation('facility_nav_contact'))
@section('description', transOrDefault($facility, 'description'))
@section('canonical', facilityRoute('contact'))
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>
@endsection

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => getLanguageKeyLocalTranslation('facility_nav_contact'),
    'heroImage' => $facility->thumbnailUrl,
])

<section class="wrapper">
    <div class="container py-3 py-md-5">
        <nav class="d-inline-block" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{ facilityRoute('home') }}">
                        {{ getLanguageKeyLocalTranslation('facility_nav_home') }}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ getLanguageKeyLocalTranslation('facility_nav_contact') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ getLanguageKeyLocalTranslation('facility_contact_page_title') }}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="uil uil-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="uil uil-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <div data-aos="fade-up" data-aos-duration="1000">
            <!-- Contact Form -->
            <form method="POST" action="{{ facilityRoute('contact.store') }}">
                @csrf

                <div class="row">
                    <!-- Name -->
                    <div class="input-group mb-2">
                        <span class="input-group-text">
                            <i class="uil uil-user"></i>
                        </span>
                        <input name="name"
                                id="nameInput"
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="{{ getLanguageKeyLocalTranslation('contact_page_name_input') }}"
                                value="{{ old('name') }}" required>
                    </div>
                    @error('name')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @else
                        <div class="mb-3"></div>
                    @enderror

                    <!-- Email -->
                    <div class="input-group mb-2">
                        <span class="input-group-text">
                            <i class="uil uil-envelope"></i>
                        </span>
                        <input name="email"
                                id="emailInput"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="{{ getLanguageKeyLocalTranslation('contact_page_email_input') }}"
                                value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @else
                        <div class="mb-3"></div>
                    @enderror

                    <!-- Phone -->
                    <div class="mb-2">
                        <input name="phone_display"
                                id="phoneInput"
                                type="text"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="{{ getLanguageKeyLocalTranslation('contact_page_phone_input') }}"
                                value="{{ old('phone_display') }}">

                        <!-- Hidden input for formatted international phone number -->
                        <input type="hidden" name="phone" id="phoneFormatted" value="{{ old('phone') }}">
                    </div>
                    @error('phone')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @else
                        <div class="mb-3"></div>
                    @enderror

                    <!-- Subject -->
                    <div class="input-group mb-2">
                        <span class="input-group-text">
                            <i class="uil uil-subject"></i>
                        </span>
                        <input name="subject"
                                id="subjectInput"
                                type="text"
                                class="form-control @error('subject') is-invalid @enderror"
                                placeholder="{{ getLanguageKeyLocalTranslation('contact_page_subject_input') }}"
                                value="{{ old('subject') }}" required>
                    </div>
                    @error('subject')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @else
                        <div class="mb-3"></div>
                    @enderror

                    <!-- Message -->
                    <div class="input-group mb-2">
                        <span class="input-group-text">
                            <i class="uil uil-comment-message"></i>
                        </span>
                        <textarea name="message"
                                    id="messageInput"
                                    class="form-control @error('message') is-invalid @enderror"
                                    placeholder="{{ getLanguageKeyLocalTranslation('contact_page_message_input') }}"
                                    rows="8" required>{{ old('message') }}</textarea>
                    </div>
                    @error('message')
                        <div class="text-danger mb-3">{{ $message }}</div>
                    @else
                        <div class="mb-3"></div>
                    @enderror

                    <!-- reCAPTCHA v2 -->
                    <div class="mb-3">
                        <div class="g-recaptcha"
                                data-sitekey="{{ getSetting('google_recaptcha_public_key')->value }}"
                                data-theme="light"
                                data-size="normal">
                        </div>
                        @error('g-recaptcha-response')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            {{ getLanguageKeyLocalTranslation('contact_page_submit_button') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script>
<script src="https://www.google.com/recaptcha/api.js?hl={{ getCurrentLanguage() }}" async defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize intl-tel-input for phone
    const phoneInput = document.querySelector("#phoneInput");

    const iti = window.intlTelInput(phoneInput, {
        initialCountry: "sa",
        preferredCountries: ["sa", "ae", "eg"],
        separateDialCode: true,
        formatOnDisplay: true,
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
    });

    function updatePhoneValue() {
        if (iti && phoneInput.value.trim() !== '') {
            try {
                const fullPhoneNumber = iti.getNumber();
                if (fullPhoneNumber) {
                    document.querySelector("#phoneFormatted").value = fullPhoneNumber;
                }
            } catch (error) {
                console.log('Error getting phone number:', error);
            }
        }
    }

    phoneInput.addEventListener('blur', updatePhoneValue);

    phoneInput.addEventListener('input', function() {
        clearTimeout(phoneInput.updateTimeout);
        phoneInput.updateTimeout = setTimeout(updatePhoneValue, 500);
    });

    phoneInput.addEventListener('countrychange', updatePhoneValue);

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endsection
