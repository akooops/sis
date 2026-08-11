@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('inquiries'))

@section('content')

@php
    $gradeOptions = ['PreK', 'KG1', 'KG2', 'G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7', 'G8', 'G9', 'G10', 'G11', 'G12'];
@endphp

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

        <div class="prose mb-8 w-full" data-aos="fade-up" data-aos-duration="1500">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <form method="POST" action="{{ route('inquiries.store') }}"
            class="flex max-w-2xl flex-col gap-4" data-aos="fade-up" data-aos-duration="1000">
            @csrf

            @include('partials.flash')

            <x-field name="guardian_name" icon="uil-user">
                <input name="guardian_name" type="text"
                    class="input @error('guardian_name') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_guardian_name_input') }}"
                    value="{{ old('guardian_name') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_guardian_name_input') }}">
            </x-field>

            <x-field name="email" icon="uil-envelope">
                <input name="email" type="email"
                    class="input @error('email') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_email_input') }}"
                    value="{{ old('email') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_email_input') }}">
            </x-field>

            <x-field name="phone">
                <input name="phone_display" type="tel" data-phone-input="#phone-e164"
                    class="input @error('phone') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_phone_input') }}"
                    value="{{ old('phone_display') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_phone_input') }}">
                <input type="hidden" name="phone" id="phone-e164" value="{{ old('phone') }}">
            </x-field>

            <x-field name="student_name" icon="uil-book-reader">
                <input name="student_name" type="text"
                    class="input @error('student_name') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_student_name_input') }}"
                    value="{{ old('student_name') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_student_name_input') }}">
            </x-field>

            <x-field name="student_birthdate" icon="uil-calendar-alt">
                <input name="student_birthdate" type="text" readonly data-datepicker data-max-date="today"
                    class="input @error('student_birthdate') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_birthdate_input') }}"
                    value="{{ old('student_birthdate') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_birthdate_input') }}">
            </x-field>

            <x-field name="student_school" icon="uil-university">
                <input name="student_school" type="text"
                    class="input @error('student_school') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_student_school_input') }}"
                    value="{{ old('student_school') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_student_school_input') }}">
            </x-field>

            <x-field name="academic_year_applied" icon="uil-calendar-alt">
                <select name="academic_year_applied" class="select @error('academic_year_applied') is-invalid @enderror"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_select_academic_year') }}">
                    <option value="">{{ getLanguageKeyLocalTranslation('inquiry_page_select_academic_year') }}</option>
                    @for ($year = 2025; $year <= 2055; $year++)
                        @php $yearRange = $year . '/' . ($year + 1); @endphp
                        <option value="{{ $yearRange }}" @selected(old('academic_year_applied') === $yearRange)>
                            {{ $yearRange }}
                        </option>
                    @endfor
                </select>
            </x-field>

            <x-field name="grade_applied" icon="uil-graduation-cap">
                <select name="grade_applied" class="select @error('grade_applied') is-invalid @enderror"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_select_grade') }}">
                    <option value="">{{ getLanguageKeyLocalTranslation('inquiry_page_select_grade') }}</option>
                    @foreach ($gradeOptions as $grade)
                        <option value="{{ $grade }}" @selected(old('grade_applied') === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field name="questions" icon="uil-comment-question">
                <textarea name="questions" rows="5"
                    class="input textarea @error('questions') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('inquiry_page_questions_input') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('inquiry_page_questions_input') }}">{{ old('questions') }}</textarea>
            </x-field>

            <x-field name="g-recaptcha-response">
                <div class="g-recaptcha" data-sitekey="{{ getSetting('google_recaptcha_public_key')->value }}"
                    data-theme="light" data-size="normal"></div>
            </x-field>

            <div class="text-end">
                <button type="submit" class="btn">
                    {{ getLanguageKeyLocalTranslation('inquiry_page_submit_button') }}
                </button>
            </div>
        </form>
    </div>
</section>

@endsection

@section('script')
    <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
@endsection
