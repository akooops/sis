@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('visits'))
@section('css')
<link href="{{ URL::asset('assets/libs/calendar/main.min.css')}}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $page->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$page->getLocalTranslation('title')}}
                </h1>
            </div>
        </div>
    </div>
</section>

@if($page->menu)
<section class="wrapper bg-light-primary">
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
                    <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper bg-light">
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

<section class="wrapper bg-light" id="booking-app">
    <div class="container pt-6 pb-12">
        <h2 class="display-5 mb-3">
            {{$page->getLocalTranslation('title')}}
        </h2>

        <p class="lead fs-md">
            {{$page->getLocalTranslation('description')}}
        </p>

        <hr class="mt-2 mb-4">

        <!-- Step Indicator -->
        <div class="d-flex justify-content-center align-items-center mb-6">
            <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                <div class="step-number">1</div>
                <span>{{getLanguageKeyLocalTranslation('visit_page_step_1')}}</span>
            </div>
            <div class="step-divider"></div>
            <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                <div class="step-number">2</div>
                <span>{{getLanguageKeyLocalTranslation('visit_page_step_2')}}</span>
            </div>
            <div class="step-divider"></div>
            <div class="step" :class="{ active: currentStep === 3 }">
                <div class="step-number">3</div>
                <span>{{getLanguageKeyLocalTranslation('visit_page_step_3')}}</span>
            </div>
        </div>

        <!-- Step 1: Service Selection -->
        <div v-show="currentStep === 1">
            <!-- Success/Error Alert -->
            <div v-if="showAlert" class="alert mb-4" :class="alertType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                <div class="d-flex align-items-center">
                    <i :class="alertType === 'success' ? 'uil uil-check-circle' : 'uil uil-exclamation-triangle'" class="me-2"></i>
                    @{{ alertMessage }}
                    <button type="button" class="btn-close ms-auto" @click="showAlert = false"></button>
                </div>
            </div>

            <div class="row gx-8 gy-4">
                @foreach ($visitServices as $visitService)
                    <div class="col-md-6 col-lg-4">
                        <div class="card">
                            <figure class="visit-service-figure card-img-top overlay overlay-1 hover-scale">
                                <img src="{{$visitService->thumbnailUrl}}" alt="{{$visitService->getLocalTranslation('title')}}" />
                            </figure>
                            <div class="card-body p-4">
                                <div class="post-header">
                                    <h2 class="post-title h3 mb-3">
                                        {{$visitService->getLocalTranslation('title')}}
                                    </h2>
                                </div>
                                <div class="post-content mb-3">
                                    <p class="truncate-3-lines mb-2">{{$visitService->getLocalTranslation('description')}}</p>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#visit-service-{{$visitService->id}}">
                                        {{getLanguageKeyLocalTranslation('visits_page_read_more_cta')}}
                                    </a>
                                </div>
                                
                                <div class="post-footer">
                                    <ul class="post-meta d-flex mb-0">
                                        <li class="post-date">
                                            <i class="uil uil-clock"></i>
                                            <span>{{$visitService->formattedDuration}}</span>
                                        </li>
                                    </ul>

                                    <div class="d-flex my-2">
                                        <input type="number" 
                                               min="1" 
                                               max="{{$visitService->capacity}}" 
                                               class="form-control" 
                                               v-model="visitorCounts[{{$visitService->id}}]"
                                               :value="visitorCounts[{{$visitService->id}}] || 1">
                                        <button class="btn btn-primary ms-2" 
                                                @click="selectService({{$visitService->id}}, '{{$visitService->getLocalTranslation('title')}}', {{$visitService->capacity}})">
                                            {{getLanguageKeyLocalTranslation('visits_page_select_button')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for service details -->
                    <div class="modal fade" id="visit-service-{{$visitService->id}}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-md">
                            <div class="modal-content">
                                <div class="modal-body p-0">
                                    <button class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    <div class="row">
                                        <div>
                                            <figure class="visit-service-figure">
                                                <img src="{{$visitService->thumbnailUrl}}" alt="{{$visitService->getLocalTranslation('title')}}" />
                                            </figure>
                                        </div>
                                    </div>
                                    <div class="px-6 py-2">
                                        <h3>{{$visitService->getLocalTranslation('title')}}</h3>
                                        <p class="lead">{{$visitService->getLocalTranslation('description')}}</p>
                                        <hr class="mt-2 mb-4">
                                        <p class="mb-6">
                                            <x-markdown>{{ $visitService->getLocalTranslation('content') }}</x-markdown>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Step 2: Time Slot Selection -->
        <div v-show="currentStep === 2">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center p-2 p-sm-8">
                            <div>
                                <button class="btn btn-sm btn-primary mb-2" @click="goBackToServices()">
                                    <i class="uil uil-arrow-left"></i>
                                </button>

                                <h4 class="mb-0">@{{ selectedService.name }}</h4>
                                <small class="text-muted">
                                    {{getLanguageKeyLocalTranslation('visit_pages_visitors_count')}}: @{{ selectedService.visitorCount }}
                                </small>
                            </div>

                        </div>
                        <div class="card-body p-2 p-sm-8">
                            <div id="calendar" v-if="selectedService.id"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Confirmation -->
        <div v-show="currentStep === 3">
            <div class="row justify-content-center">
                <div class="col-10">
                    <!-- Success/Error Alert -->
                    <div v-if="showAlert" class="alert mb-4" :class="alertType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                        <div class="d-flex align-items-center">
                            <i :class="alertType === 'success' ? 'uil uil-check-circle' : 'uil uil-exclamation-triangle'" class="me-2"></i>
                            @{{ alertMessage }}
                            <button type="button" class="btn-close ms-auto" @click="showAlert = false"></button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h2>{{getLanguageKeyLocalTranslation('visits_page_confirmation_title')}}</h2>

                            <div class="mt-6" v-if="selectedTimeSlot">
                                <form @submit.prevent="confirmBooking()">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <!-- Guardian Name -->
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <i class="uil uil-user"></i>
                                                </span>
                                                <input id="nameInput" 
                                                    v-model.trim="bookingForm.guardian_name"
                                                    type="text" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.guardian_name }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_name_input')}}">   
                                            </div>
                                            <div v-if="errors.guardian_name" class="text-danger small mb-3">@{{ errors.guardian_name }}</div>
                                            <div v-else class="mb-3"></div>

                                            <!-- Email -->
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <i class="uil uil-envelope"></i>
                                                </span>
                                                <input id="emailInput" 
                                                    v-model.trim="bookingForm.email"
                                                    type="email" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.email }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_email_input')}}">   
                                            </div>
                                            <div v-if="errors.email" class="text-danger small mb-3">@{{ errors.email }}</div>
                                            <div v-else class="mb-3"></div>

                                            <!-- Phone -->
                                            <div class="mb-2">
                                                <input id="phoneInput" 
                                                    v-model.trim="bookingForm.phone"
                                                    type="text" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.phone }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_phone_input')}}">                                 
                                            </div>
                                            <div v-if="errors.phone" class="text-danger small mb-3">@{{ errors.phone }}</div>
                                            <div v-else class="mb-3"></div>

                                            <!-- Student Name -->
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <i class="uil uil-book-reader"></i>
                                                </span>
                                                <input id="studentNameInput" 
                                                    v-model.trim="bookingForm.student_name"
                                                    type="text" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.student_name }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_name_input')}}">   
                                            </div>
                                            <div v-if="errors.student_name" class="text-danger small mb-3">@{{ errors.student_name }}</div>
                                            <div v-else class="mb-3"></div>

                                            <!-- Student Grade -->
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <i class="uil uil-code-branch"></i>
                                                </span>
                                                <input id="gradeInput" 
                                                    v-model.trim="bookingForm.student_grade"
                                                    type="text" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.student_grade }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_grade_input')}}">   
                                            </div>
                                            <div v-if="errors.student_grade" class="text-danger small mb-3">@{{ errors.student_grade }}</div>
                                            <div v-else class="mb-3"></div>

                                            <!-- Student School -->
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <i class="uil uil-university"></i>
                                                </span>
                                                <input id="schoolInput" 
                                                    v-model.trim="bookingForm.student_school"
                                                    type="text" 
                                                    class="form-control"
                                                    :class="{ 'is-invalid': errors.student_school }"
                                                    placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_school_input')}}">   
                                            </div>
                                            <div v-if="errors.student_school" class="text-danger small mb-3">@{{ errors.student_school }}</div>
                                            <div v-else class="mb-3"></div>
                                        </div>

                                        <div class="col-md-6">
                                            <p><strong>{{getLanguageKeyLocalTranslation('visit_pages_selected_service')}}:</strong> @{{ selectedService.name }}</p>
                                            <p><strong>{{getLanguageKeyLocalTranslation('visits_page_date_time')}}:</strong> @{{ formatDateTime(selectedTimeSlot.start) }}</p>
                                            <p><strong>{{getLanguageKeyLocalTranslation('visit_pages_visitors_count')}}:</strong> @{{ selectedService.visitorCount }}</p>

                                            <div class="d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary" :disabled="bookingInProgress">
                                                    <span v-if="bookingInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                                    {{getLanguageKeyLocalTranslation('visits_page_confirm_button')}}
                                                </button>
                                            </div>
                                        </div>  
                                    </div>
                                </form>              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</section>

<!-- Pass data to Vue -->
<script>
window.visitServicesData = {
    @foreach ($visitServices as $visitService)
    "{{$visitService->id}}": {
        timeSlots: [
            @foreach ($visitService->upcomingTimeSlots as $timeSlot)
            {
                id: {{$timeSlot->id}},
                starts_at: "{{$timeSlot->starts_at}}"
            },
            @endforeach
        ]
    },
    @endforeach
};
</script>
@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/calendar/main.min.js')}}"></script>
<script src='{{ URL::asset('assets/libs/calendar/locales-all.min.js')}}'></script>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script> <!-- Add this -->

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<script>
const { createApp, nextTick } = Vue;

createApp({
    data() {
        return {
            currentStep: 1,
            calendar: null,
            visitorCounts: {},
            selectedService: {
                id: null,
                name: '',
                visitorCount: 1,
                capacity: 0
            },
            selectedTimeSlot: null,
            bookingInProgress: false,
            iti: null,
            
            // Form data
            bookingForm: {
                guardian_name: '',
                email: '',
                phone: '',
                student_name: '',
                student_grade: '',
                student_school: ''
            },
            
            // Error handling
            errors: {},
            showAlert: false,
            alertType: 'success', // 'success' or 'error'
            alertMessage: ''
        }
    },
    methods: {
        selectService(serviceId, serviceName, capacity) {
            const visitorCount = this.visitorCounts[serviceId] || 1;
            
            this.selectedService = {
                id: serviceId,
                name: serviceName,
                visitorCount: visitorCount,
                capacity: capacity
            };
            
            this.currentStep = 2;
            this.$nextTick(() => {
                this.initializeCalendar(serviceId);
            });
        },
        
        initializeCalendar(serviceId) {
            if (this.calendar) {
                this.calendar.destroy();
            }
            
            const timeSlots = window.visitServicesData[serviceId]?.timeSlots || [];
            
            const calendarEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                locale: '{{app()->getLocale()}}',
                initialView: 'dayGridWeek',
                headerToolbar: {
                    left: 'prev,next',
                    right: 'dayGridMonth,dayGridWeek'
                },
                height: 'auto',
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                events: timeSlots.map(slot => ({
                    id: slot.id,
                    start: slot.starts_at,
                    backgroundColor: '#2A5D91'           
                })),
                eventClick: (info) => {
                    this.selectTimeSlot(info.event);
                }
            });
            
            this.calendar.render();
        },
        
        selectTimeSlot(event) {
            this.selectedTimeSlot = {
                id: event.id,
                start: event.start,
                end: event.end,
                availableSpots: event.extendedProps?.availableSpots
            };
            
            this.currentStep = 3;
            
            nextTick(() => {
                this.initIntlTelInput();
            });
        },

        initIntlTelInput() {
            const input = document.getElementById('phoneInput');
            if (!input) return;

            if (input.iti) {
                input.iti.destroy();
                input.iti = null;
            }

            this.iti = window.intlTelInput(input, {
                initialCountry: "sa",
                preferredCountries: ["sa", "ae", "eg"],
                separateDialCode: true,
                formatOnDisplay: true,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
            });

            input.iti = this.iti;
        },

        async confirmBooking() {
            this.bookingInProgress = true;
            this.errors = {};
            this.showAlert = false;
            
            // Get the full international phone number
            const fullPhoneNumber = this.iti ? this.iti.getNumber() : this.bookingForm.phone;

            try {
                const response = await fetch('{{route("visit-bookings.store", $visitService->id)}}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        guardian_name: this.bookingForm.guardian_name.trim() || null,
                        email: this.bookingForm.email.trim() || null,
                        phone: fullPhoneNumber.trim() || null,
                        student_name: this.bookingForm.student_name.trim() || null,
                        student_grade: this.bookingForm.student_grade.trim() || null,
                        student_school: this.bookingForm.student_school.trim() || null,
                        visitors_count: this.selectedService.visitorCount,
                        visit_time_slot_id: this.selectedTimeSlot.id
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.status === 'success') {
                    this.showSuccessAlert('{{getLanguageKeyLocalTranslation('visit_pages_booking_success')}}');
                    this.resetBooking();
                } else {
                    if (result.errors) {
                        this.handleValidationErrors(result.errors);
                    } else {
                        this.showErrorAlert(result.message || '{{getLanguageKeyLocalTranslation('visit_pages_booking_error')}}');
                    }
                }
            } catch (error) {
                this.showErrorAlert('{{getLanguageKeyLocalTranslation('visit_pages_booking_error')}}');
            } finally {
                this.bookingInProgress = false;
            }
        },

        handleValidationErrors(errors) {
            this.errors = {};
            
            // Handle Laravel validation errors format
            if (Array.isArray(errors)) {
                errors.forEach(error => {
                    this.errors[error.field] = error.message;
                });
            } else {
                // Handle standard Laravel validation errors
                Object.keys(errors).forEach(field => {
                    this.errors[field] = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                });
            }
        },

        showSuccessAlert(message) {
            this.alertType = 'success';
            this.alertMessage = message;
            this.showAlert = true;
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                this.showAlert = false;
            }, 5000);
        },

        showErrorAlert(message) {
            this.alertType = 'error';
            this.alertMessage = message;
            this.showAlert = true;
            
            // Auto hide after 8 seconds
            setTimeout(() => {
                this.showAlert = false;
            }, 8000);
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('{{app()->getLocale()}}');
        },
        
        resetBooking() {
            this.currentStep = 1;
            this.selectedService = { id: null, name: '', visitorCount: 1, capacity: 0 };
            this.selectedTimeSlot = null;
            this.bookingForm = {
                guardian_name: '',
                email: '',
                phone: '',
                student_name: '',
                student_grade: '',
                student_school: ''
            };
            this.errors = {};
            
            if (this.calendar) {
                this.calendar.destroy();
                this.calendar = null;
            }
        },
        
        goBackToServices() {
            this.currentStep = 1;
            this.selectedService = { id: null, name: '', visitorCount: 1, capacity: 0 };
            if (this.calendar) {
                this.calendar.destroy();
                this.calendar = null;
            }
        }
    },
    
    mounted() {
        // Initialize visitor counts for all services
        @foreach ($visitServices as $visitService)
            this.visitorCounts[{{$visitService->id}}] = 1;
        @endforeach
    }
}).mount('#booking-app');
</script>

@endsection
