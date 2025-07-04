@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('visits'))
@section('css')
<link href="{{ URL::asset('assets/libs/calendar/main.min.css')}}" rel="stylesheet" type="text/css" />
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
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}"
                    href="{{ $menuUrl }}">
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

        <div class="w-100" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <hr class="mt-4 mb-8" data-aos="fade-up" data-aos-duration="1500">

        <div class="visit-service-container" id="booking-app">
            <!-- Step Indicator -->
            <div class="d-flex flex-column align-items-center mb-6">
                <!-- Step Indicator -->
                <div class="row w-100 step-indicator">
                    <div class="col-12 col-lg-4 step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                        <span>{{getLanguageKeyLocalTranslation('visit_page_step_1')}}</span>
                    </div>
                    <div class="col-12 col-lg-4 step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                        <span>{{getLanguageKeyLocalTranslation('visit_page_step_2')}}</span>
                    </div>
                    <div class="col-12 col-lg-4 step" :class="{ active: currentStep === 3 }">
                        <span>{{getLanguageKeyLocalTranslation('visit_page_step_3')}}</span>
                    </div>
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
                                <figure class="hover-scale">
                                    <img src="{{$visitService->thumbnailUrl}}" alt="{{$visitService->getLocalTranslation('title')}}" />
                                </figure>
                                
                                <div class="card-body p-4">
                                    <h2 class="mb-2">
                                        {{$visitService->getLocalTranslation('title')}}
                                    </h2>

                                    <p class="truncate-3-lines mb-6">
                                        {{$visitService->getLocalTranslation('description')}}...

                                        <a href="#" data-bs-toggle="modal" data-bs-target="#visit-service-{{$visitService->id}}">
                                            {{getLanguageKeyLocalTranslation('visits_page_read_more_cta')}}
                                        </a>
                                    </p>
                                    
                                    <ul class="post-meta d-flex mb-0">
                                        <li class="post-date">
                                            <i class="uil uil-clock"></i>
                                            <span>{{$visitService->formattedDuration}}</span>
                                        </li>
                                    </ul>
                                    <div class="d-flex my-2 gap-2">
                                        <div class="visitor-counter w-100">
                                            <span class="visitor-icon">
                                                <i class="uil uil-user"></i>
                                            </span>
                                            <div class="counter-controls">
                                                <input type="number" 
                                                    min="1" 
                                                    class="counter-input" 
                                                    v-model="visitorCounts[{{$visitService->id}}]"
                                                    @input="updateVisitorCount({{$visitService->id}})"
                                                    :value="visitorCounts[{{$visitService->id}}] || 1"
                                                    readonly>

                                                <div class="counter-buttons">
                                                    <button @click="incrementVisitors({{$visitService->id}})" class="counter-btn">▲</button>
                                                    <button @click="decrementVisitors({{$visitService->id}})" 
                                                            :disabled="(visitorCounts[{{$visitService->id}}] || 1) <= 1" 
                                                            class="counter-btn">▼</button>
                                                </div>

                                                <button class="btn flex-grow-1" 
                                                        @click="selectService({{$visitService->id}}, '{{$visitService->getLocalTranslation('title')}}', {{$visitService->capacity}})">
                                                    {{getLanguageKeyLocalTranslation('visits_page_select_button')}}
                                                </button>
                                            </div>
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
                                                <figure>
                                                    <img src="{{$visitService->thumbnailUrl}}" 
                                                         alt="{{$visitService->getLocalTranslation('title')}}" />
                                                </figure>
                                            </div>
                                        </div>

                                        <div class="px-6 py-2">
                                            <h3>{{$visitService->getLocalTranslation('title')}}</h3>

                                            <p class="lead">{{$visitService->getLocalTranslation('description')}}</p>

                                            <hr class="mt-2 mb-4">

                                            <p class="mb-6">
                                                <div class="page-content">
                                                    {!! $visitService->getLocalTranslation('content') !!}
                                                </div>
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

                                    <h2 class="mb-0">@{{ selectedService.name }}</h2>
                                    <small class="text-muted">
                                        {{getLanguageKeyLocalTranslation('visit_pages_visitors_count')}}: @{{ selectedService.visitorCount }}
                                    </small>
                                </div>
                            </div>
                            <div class="card-body p-2 p-sm-8">
                                                <div class="time-slot-legend">
                    <div class="legend-item">
                        <div class="legend-color legend-available"></div>
                        <span>{{getLanguageKeyLocalTranslation('visit_pages_available_slots')}}</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-fully-booked"></div>
                        <span>{{getLanguageKeyLocalTranslation('visit_pages_fully_booked')}}</span>
                    </div>
                </div>

                                <div id="calendar" v-if="selectedService.id"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-show="currentStep === 3">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <!-- Success/Error Alert -->
                        <div v-if="showAlert" class="alert mb-4" :class="alertType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                            <div class="d-flex align-items-center">
                                <i :class="alertType === 'success' ? 'uil uil-check-circle' : 'uil uil-exclamation-triangle'" class="me-2"></i>
                                @{{ alertMessage }}
                                <button type="button" class="btn-close ms-auto" @click="showAlert = false"></button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center p-2 p-sm-8">
                                <div>
                                    <button class="btn btn-sm btn-primary mb-2" @click="goBackToSlots()">
                                        <i class="uil uil-arrow-left"></i>
                                    </button>

                                    <h2 class="mb-0">@{{ selectedService.name }}</h2>
                                    <small class="text-muted">
                                        {{getLanguageKeyLocalTranslation('visit_pages_visitors_count')}}: @{{ selectedService.visitorCount }}
                                    </small>
                                </div>
                            </div>

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
                                                <div v-if="errors.guardian_name" class="text-danger mb-3">@{{ errors.guardian_name }}</div>
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
                                                <div v-if="errors.email" class="text-danger mb-3">@{{ errors.email }}</div>
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
                                                <div v-if="errors.phone" class="text-danger mb-3">@{{ errors.phone }}</div>
                                                <div v-else class="mb-3"></div>

                                                <!-- Students Information -->
                                                <div v-for="(student, index) in bookingForm.students" :key="index" class="student-group mb-4">
                                                    <h6 class="mb-3 text-primary">Student @{{ index + 1 }}</h6>
                                                    
                                                    <!-- Student Name -->
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text">
                                                            <i class="uil uil-book-reader"></i>
                                                        </span>
                                                        <input :id="'studentNameInput' + index" 
                                                            v-model.trim="student.name"
                                                            type="text" 
                                                            class="form-control"
                                                            :class="{ 'is-invalid': errors['students.' + index + '.name'] }"
                                                            placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_name_input')}}">   
                                                    </div>
                                                    <div v-if="errors['students.' + index + '.name']" class="text-danger mb-3">@{{ errors['students.' + index + '.name'] }}</div>
                                                    <div v-else class="mb-3"></div>

                                                    <!-- Student Grade -->
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text">
                                                            <i class="uil uil-code-branch"></i>
                                                        </span>
                                                        <input :id="'gradeInput' + index" 
                                                            v-model.trim="student.grade"
                                                            type="text" 
                                                            class="form-control"
                                                            :class="{ 'is-invalid': errors['students.' + index + '.grade'] }"
                                                            placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_grade_input')}}">   
                                                    </div>
                                                    <div v-if="errors['students.' + index + '.grade']" class="text-danger mb-3">@{{ errors['students.' + index + '.grade'] }}</div>
                                                    <div v-else class="mb-3"></div>

                                                    <!-- Student School -->
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text">
                                                            <i class="uil uil-university"></i>
                                                        </span>
                                                        <input :id="'schoolInput' + index" 
                                                            v-model.trim="student.school"
                                                            type="text" 
                                                            class="form-control"
                                                            :class="{ 'is-invalid': errors['students.' + index + '.school'] }"
                                                            placeholder="{{getLanguageKeyLocalTranslation('visits_page_student_school_input')}}">   
                                                    </div>
                                                    <div v-if="errors['students.' + index + '.school']" class="text-danger mb-3">@{{ errors['students.' + index + '.school'] }}</div>
                                                    <div v-else class="mb-3"></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6 text-center text-md-start mt-4 mt-md-0">
                                                <p><strong>{{getLanguageKeyLocalTranslation('visit_pages_selected_service')}}:</strong> @{{ selectedService.name }}</p>
                                                <p><strong>{{getLanguageKeyLocalTranslation('visits_page_date_time')}}:</strong> @{{ formatDateTime(selectedTimeSlot.start) }}</p>
                                                <p><strong>{{getLanguageKeyLocalTranslation('visit_pages_visitors_count')}}:</strong> @{{ selectedService.visitorCount }}</p>

                                                <div class="d-flex justify-content-center justify-content-md-end">
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
                starts_at: "{{$timeSlot->starts_at}}",
                ends_at: "{{$timeSlot->ends_at}}",
                remaining_capacity: "{{$timeSlot->remaining_capacity}}",
                total_capacity: "{{$timeSlot->capacity}}"
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
                students: []
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
            const selectedVisitorCount = this.selectedService.visitorCount;
            
            const calendarEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                locale: '{{app()->getLocale()}}',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next',
                    right: 'dayGridMonth,timeGridWeek'
                },
                height: 'auto',
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                
                // Create events with dynamic colors based on capacity only
                events: timeSlots.map(slot => {
                    let eventColor, eventTitle, eventClass, isClickable;
                    
                    if (slot.remaining_capacity <= 0) {
                        // Fully booked
                        eventColor = '#dc3545'; // Red
                        eventTitle = `Fully Booked (${slot.total_capacity}/${slot.total_capacity})`;
                        eventClass = ['fully-booked-slot'];
                        isClickable = false;
                    } else {
                        // Available - any remaining capacity means the slot is bookable
                        eventColor = '#198754'; // Green
                        eventTitle = `{{getLanguageKeyLocalTranslation("visit_pages_available_slots")}} (${slot.remaining_capacity}/${slot.total_capacity})`;
                        eventClass = ['available-slot'];
                        isClickable = true;
                    }
                    
                    return {
                        id: slot.id,
                        start: slot.starts_at,
                        end: slot.ends_at,
                        title: eventTitle,
                        backgroundColor: eventColor,
                        borderColor: eventColor,
                        textColor: '#ffffff',
                        classNames: eventClass,
                        extendedProps: {
                            remaining_capacity: slot.remaining_capacity,
                            total_capacity: slot.total_capacity,
                            clickable: isClickable,
                            fully_booked: slot.remaining_capacity <= 0
                        }
                    };
                }),
                
                // Handle event clicks with capacity-only logic
                eventClick: (info) => {
                    const extendedProps = info.event.extendedProps;
                    
                    if (extendedProps.fully_booked) {
                        this.showErrorAlert('This time slot is fully booked. Please choose another time slot.');
                        return false;
                    } else {
                        this.selectTimeSlot(info.event);
                    }
                },
                
                // Enhanced styling for events
                eventDidMount: function(info) {
                    const extendedProps = info.event.extendedProps;
                    
                    if (!extendedProps.clickable) {
                        info.el.style.cursor = 'not-allowed';
                        info.el.style.opacity = '0.6';
                    } else {
                        info.el.style.cursor = 'pointer';
                    }
                }
            });
            
            this.calendar.render();
        },


        selectTimeSlot(event) {
            const extendedProps = event.extendedProps;
            
            // Check capacity constraints
            if (extendedProps.fully_booked) {
                this.showErrorAlert('This time slot is fully booked. Please choose another time slot.');
                return;
            }
            
            this.selectedTimeSlot = {
                id: event.id,
                start: event.start,
                end: event.end,
                remaining_capacity: extendedProps.remaining_capacity,
                total_capacity: extendedProps.total_capacity
            };
            
            // Initialize students array based on visitor count
            this.bookingForm.students = [];
            for (let i = 0; i < this.selectedService.visitorCount; i++) {
                this.bookingForm.students.push({
                    name: '',
                    grade: '',
                    school: ''
                });
            }
            
            this.currentStep = 3;
            
            nextTick(() => {
                this.initIntlTelInput();
            });
        },

        incrementVisitors(serviceId) {
            const currentCount = this.visitorCounts[serviceId] || 1;                        
            this.visitorCounts[serviceId] = currentCount + 1;
            this.updateVisitorCount(serviceId);
        },

        decrementVisitors(serviceId) {
            const currentCount = this.visitorCounts[serviceId] || 1;

            if (currentCount > 1) {
                this.visitorCounts[serviceId] = currentCount - 1;;
                this.updateVisitorCount(serviceId);
            }
        },

        updateVisitorCount(serviceId) {
            if (this.selectedService.id === serviceId && this.currentStep === 2) {
                this.selectedService.visitorCount = this.visitorCounts[serviceId] || 1;
                // No need to refresh calendar since visitor count doesn't affect capacity anymore
            }
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
                const response = await fetch(`/visit-services/${this.selectedService.id}/visit-bookings`, { 
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
                        students: this.bookingForm.students,
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

                        if (result.errors.visit_time_slot_id) {
                            this.showErrorAlert(result.message || '{{getLanguageKeyLocalTranslation('visit_pages_insufficient_capacity')}}');
                        }
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
                students: []
            };
            this.errors = {};
            
            if (this.calendar) {
                this.calendar.destroy();
                this.calendar = null;
            }
        },
                
        goBackToSlots() {
            this.currentStep = 2;
            this.selectedTimeSlot = null;
            
            this.errors = {};
            this.showAlert = false;

            this.$nextTick(() => {
                this.initializeCalendar(this.selectedService.id);
            });
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