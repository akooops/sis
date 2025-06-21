
@php
    $googleMapUrl = getSetting('google_maps_url');
@endphp

@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('contact'))
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>
@endsection

@section('content')
<section class="wrapper banners-section">
    <div class="swiper-container" 
        data-margin="0" 
        data-autoplay="true" 
        data-autoplaytime="7000" 
        data-nav="true" 
        data-dots="true" 
        data-items="1">
        
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $page->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$page->getLocalTranslation('title')}}
                                    </h1>
                                </div>
                                <!--/.col -->
                            </div>     
                            <!--/.row -->      
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.container -->
                </div>
                <!--/.swiper-slide -->            
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->

@if($page->menu)
<section class="wrapper page-menu-section">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($page->menu->items as $menuItem)
                @php
                    $menuUrl = $menuItem->page
                        ? route('page', ['slug' => $menuItem->page->slug])
                        : $menuItem->url;
                    $currentUrl = url()->current();
                @endphp

                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}" href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper">
    <div class="container py-3 py-md-5">
        <nav class="d-inline-block" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{route('index')}}">
                        {{getLanguageKeyLocalTranslation('breadcrumbs_index_page_title')}}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{$page->getLocalTranslation('title')}}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
   <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{$page->getLocalTranslation('title')}}
        </h2>

        <p data-aos="fade-up" data-aos-duration="1500">
            {{$page->getLocalTranslation('description')}}
        </p>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">

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

        <div class="col-12 mx-auto mb-8" data-aos="fade-up" data-aos-duration="1000">
            <div class="card">
                @if($googleMapUrl)
                <div class="row gx-0">
                    <div class="map map-full rounded-top rounded-lg-start">
                        <iframe id="google-map" src="{{$googleMapUrl->value}}" style="width:100%; height: 500px; border:0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                @endif

                <div class="row p-10">
                    <div class="d-flex flex-row col-12 col-md-4 mb-4 mb-md-0">
                        <div>
                            <div class="icon text-primary fs-28 me-4 mt-n1"> <i class="uil uil-location-pin-alt"></i> </div>
                        </div>
                        <div class="align-self-start justify-content-start">
                            <h5 class="mb-1">
                                {{getLanguageKeyLocalTranslation('contact_page_address_title')}}                    
                            </h5>
                            <address>
                                {{getLanguageKeyLocalTranslation('get_in_touch_address')}}
                            </address>
                        </div>
                    </div>
                    <!--/div -->
                    <div class="d-flex flex-row col-12 col-md-4 mb-4 mb-md-0">
                        <div>
                            <div class="icon text-primary fs-28 me-4 mt-n1"> <i class="uil uil-phone-volume"></i> </div>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                {{getLanguageKeyLocalTranslation('contact_page_phone_title')}}                    
                            </h5>
                            <p>
                                <a href="tel:{{getLanguageKeyLocalTranslation('get_in_touch_phone')}}">{{getLanguageKeyLocalTranslation('get_in_touch_phone')}}</a> <br class="d-none d-md-block">
                            </p>
                        </div>
                    </div>
                    <!--/div -->
                    <div class="d-flex flex-row col-12 col-md-4 mb-4 mb-md-0">
                        <div>
                            <div class="icon text-primary fs-28 me-4 mt-n1"> <i class="uil uil-envelope"></i> </div>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                {{getLanguageKeyLocalTranslation('contact_page_email_title')}}                 
                            </h5>
                            <p class="mb-0">
                                <a href="mailto:{{getLanguageKeyLocalTranslation('get_in_touch_email')}}" class="link-body">
                                    {{getLanguageKeyLocalTranslation('get_in_touch_email')}}
                                </a>
                            </p>
                        </div>
                    </div>
                    <!--/div -->
                </div>
            </div>
            <!-- /.card -->
        </div>
        
        <div data-aos="fade-up" data-aos-duration="1000">
            <!-- Contact Form -->
            <form method="POST" action="{{ route('contact-submissions.store') }}">
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
                                placeholder="{{getLanguageKeyLocalTranslation('contact_page_name_input')}}"
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
                                placeholder="{{getLanguageKeyLocalTranslation('contact_page_email_input')}}"
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
                                placeholder="{{getLanguageKeyLocalTranslation('contact_page_phone_input')}}"
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
                                placeholder="{{getLanguageKeyLocalTranslation('contact_page_subject_input')}}"
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
                                    placeholder="{{getLanguageKeyLocalTranslation('contact_page_message_input')}}"
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
                            {{getLanguageKeyLocalTranslation('contact_page_submit_button')}}
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

    // Function to update the phone input value with formatted international number
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

    // Update phone field when user finishes typing
    phoneInput.addEventListener('blur', function() {
        updatePhoneValue();
    });

    // Update phone field when user changes input
    phoneInput.addEventListener('input', function() {
        clearTimeout(phoneInput.updateTimeout);
        phoneInput.updateTimeout = setTimeout(() => {
            updatePhoneValue();
        }, 500);
    });

    // Update phone field when country is changed
    phoneInput.addEventListener('countrychange', function() {
        updatePhoneValue();
    });

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
