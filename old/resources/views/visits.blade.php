@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('visits'))

@section('content')

@php
    // Everything the booking island needs. Copy is resolved here so the DB
    // translations stay the single source of truth.
    $bookingProps = [
        'locale' => app()->getLocale(),
        'csrf' => csrf_token(),
        'bookingUrl' => route('visit-bookings.store', ['visitService' => '__ID__']),
        'services' => $visitServices->map(fn ($service) => [
            'id' => $service->id,
            'title' => $service->getLocalTranslation('title'),
            'description' => $service->getLocalTranslation('description'),
            'content' => $service->getLocalTranslation('content'),
            'thumbnail' => $service->thumbnailUrl,
            'duration' => $service->formattedDuration,
            'capacity' => $service->capacity,
            'timeSlots' => $service->upcomingTimeSlots->map(fn ($slot) => [
                'id' => $slot->id,
                'starts_at' => (string) $slot->starts_at,
                'ends_at' => (string) $slot->ends_at,
                'remaining_capacity' => $slot->remaining_capacity,
                'total_capacity' => $slot->capacity,
            ])->values(),
        ])->values(),
        't' => [
            'step_1' => getLanguageKeyLocalTranslation('visit_page_step_1'),
            'step_2' => getLanguageKeyLocalTranslation('visit_page_step_2'),
            'step_3' => getLanguageKeyLocalTranslation('visit_page_step_3'),
            'read_more' => getLanguageKeyLocalTranslation('visits_page_read_more_cta'),
            'select' => getLanguageKeyLocalTranslation('visits_page_select_button'),
            'visitors_count' => getLanguageKeyLocalTranslation('visit_pages_visitors_count'),
            'available_slots' => getLanguageKeyLocalTranslation('visit_pages_available_slots'),
            'fully_booked' => getLanguageKeyLocalTranslation('visit_pages_fully_booked'),
            'confirmation_title' => getLanguageKeyLocalTranslation('visits_page_confirmation_title'),
            'name_input' => getLanguageKeyLocalTranslation('visits_page_name_input'),
            'email_input' => getLanguageKeyLocalTranslation('visits_page_email_input'),
            'phone_input' => getLanguageKeyLocalTranslation('visits_page_phone_input'),
            'student' => getLanguageKeyLocalTranslation('visits_page_student_label') ?: 'Student',
            'student_name_input' => getLanguageKeyLocalTranslation('visits_page_student_name_input'),
            'student_grade_input' => getLanguageKeyLocalTranslation('visits_page_student_grade_input'),
            'student_school_input' => getLanguageKeyLocalTranslation('visits_page_student_school_input'),
            'selected_service' => getLanguageKeyLocalTranslation('visit_pages_selected_service'),
            'date_time' => getLanguageKeyLocalTranslation('visits_page_date_time'),
            'confirm' => getLanguageKeyLocalTranslation('visits_page_confirm_button'),
            'booking_success' => getLanguageKeyLocalTranslation('visit_pages_booking_success'),
            'booking_error' => getLanguageKeyLocalTranslation('visit_pages_booking_error'),
            'insufficient_capacity' => getLanguageKeyLocalTranslation('visit_pages_insufficient_capacity'),
            'slot_fully_booked' => getLanguageKeyLocalTranslation('visit_pages_slot_fully_booked')
                ?: 'This time slot is fully booked. Please choose another time slot.',
        ],
    ];
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

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <hr class="mb-8 mt-4 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div data-island="visit-booking" data-props="{{ json_encode($bookingProps) }}"></div>
    </div>
</section>

@endsection
