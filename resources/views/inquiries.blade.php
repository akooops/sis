@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('inquiries'))
@section('css')
<link href="{{ URL::asset('assets/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
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

        <div data-aos="fade-up" data-aos-duration="1000">
            <!-- Inquiry Form -->
            <form method="POST" action="{{ route('inquiries.store') }}">
                @csrf
                
                <div class="row">
                    <div class="col-12 col-lg-6">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="uil uil-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
        
                        <!-- Guardian Name -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-user"></i>
                            </span>
                            <input name="guardian_name" 
                                id="guardianNameInput" 
                                type="text" 
                                class="form-control @error('guardian_name') is-invalid @enderror" 
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_guardian_name_input')}}"
                                value="{{ old('guardian_name') }}">
                        </div>
                        @error('guardian_name')
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
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_email_input')}}"
                                value="{{ old('email') }}">
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
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_phone_input')}}"
                                value="{{ old('phone_display') }}">
                            
                            <!-- Hidden input for formatted international phone number -->
                            <input type="hidden" name="phone" id="phoneFormatted" value="{{ old('phone') }}">
                        </div>
                        @error('phone')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
        
                        <!-- Student Name -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-book-reader"></i>
                            </span>
                            <input name="student_name" 
                                id="studentNameInput" 
                                type="text" 
                                class="form-control @error('student_name') is-invalid @enderror" 
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_student_name_input')}}"
                                value="{{ old('student_name') }}">
                        </div>
                        @error('student_name')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
                        <!-- Student Birthdate -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-calendar-alt"></i>
                            </span>
                            <input name="student_birthdate" 
                                id="birthdateInput" 
                                type="text" 
                                class="form-control flatpickr-input @error('student_birthdate') is-invalid @enderror"
                                data-provider="flatpickr" 
                                data-date-format="Y-m-d"
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_birthdate_input')}}"
                                value="{{ old('student_birthdate') }}"
                                readonly="readonly">
                        </div>
                        @error('student_birthdate')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
                        <!-- Student School -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-university"></i>
                            </span>
                            <input name="student_school" 
                                id="studentSchoolInput" 
                                type="text" 
                                class="form-control @error('student_school') is-invalid @enderror" 
                                placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_student_school_input')}}"
                                value="{{ old('student_school') }}">
                        </div>
                        @error('student_school')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
                        <!-- Academic Year Applied -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-calendar-alt"></i>
                            </span>
                            <select name="academic_year_applied" 
                                    class="form-control @error('academic_year_applied') is-invalid @enderror">
                                <option value="">{{getLanguageKeyLocalTranslation('inquiry_page_select_academic_year')}}</option>
                                @for($year = 2025; $year <= 2055; $year++)
                                    @php $yearRange = $year . '/' . ($year + 1); @endphp
                                    <option value="{{ $yearRange }}" {{ old('academic_year_applied') == $yearRange ? 'selected' : '' }}>
                                        {{ $yearRange }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        @error('academic_year_applied')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
                        <!-- Grade Applied -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-graduation-cap"></i>
                            </span>
                            <select name="grade_applied" 
                                    class="form-control @error('grade_applied') is-invalid @enderror">
                                <option value="">{{getLanguageKeyLocalTranslation('inquiry_page_select_grade')}}</option>
                                @php
                                    $grades = ['PreK', 'KG1', 'KG2', 'G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7', 'G8', 'G9', 'G10', 'G11', 'G12'];
                                @endphp
                                @foreach($grades as $grade)
                                    <option value="{{ $grade }}" {{ old('grade_applied') == $grade ? 'selected' : '' }}>
                                        {{ $grade }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('grade_applied')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @else
                            <div class="mb-3"></div>
                        @enderror
        
                        <!-- Questions -->
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                <i class="uil uil-comment-question"></i>
                            </span>
                            <textarea name="questions" 
                                    id="questionsInput" 
                                    class="form-control @error('questions') is-invalid @enderror" 
                                    placeholder="{{getLanguageKeyLocalTranslation('inquiry_page_questions_input')}}"
                                    rows="5">{{ old('questions') }}</textarea>
                        </div>
                        @error('questions')
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
                                {{getLanguageKeyLocalTranslation('inquiry_page_submit_button')}}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/flatpickr/flatpickr.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script>
<script src="https://www.google.com/recaptcha/api.js?hl={{ getCurrentLanguage() }}" async defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for birthdate
    flatpickr("#birthdateInput", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        yearRange: [1950, new Date().getFullYear()],
        locale: "{{ getCurrentLanguage() }}"
    });

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
        // Debounce the update to avoid too frequent calls
        clearTimeout(phoneInput.updateTimeout);
        phoneInput.updateTimeout = setTimeout(() => {
            updatePhoneValue();
        }, 500); // Wait 500ms after user stops typing
    });

    // Update phone field when country is changed
    phoneInput.addEventListener('countrychange', function() {
        updatePhoneValue();
    });

    // Update phone field when user pastes content
    phoneInput.addEventListener('paste', function() {
        setTimeout(() => {
            updatePhoneValue();
        }, 100); // delay to let paste complete
    });
});
</script>
@endsection
