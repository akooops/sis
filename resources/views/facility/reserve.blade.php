@extends('facility.layouts.master')
@section('title', getLanguageKeyLocalTranslation('facility_nav_reserve'))
@section('description', transOrDefault($facility, 'description'))
@section('canonical', facilityRoute('reserve'))
@section('css')
<link href="{{ URL::asset('assets/libs/calendar/main.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>
@endsection

@section('content')

@include('facility.partials.hero', [
    'heroTitle' => getLanguageKeyLocalTranslation('facility_nav_reserve'),
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
                    {{ getLanguageKeyLocalTranslation('facility_nav_reserve') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ getLanguageKeyLocalTranslation('facility_reserve_page_title') }}
        </h2>

        <p data-aos="fade-up" data-aos-duration="1000">
            {{ getLanguageKeyLocalTranslation('facility_reserve_page_intro') }}
        </p>

        <hr class="mt-2 mb-8" data-aos="fade-up" data-aos-duration="1000">

        <div class="visit-service-container" id="reservation-app">
            <!-- Step Indicator -->
            <div class="d-flex flex-column align-items-center mb-6">
                <div class="row w-100 step-indicator">
                    <div class="col-12 col-lg-6 step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                        <span>{{ getLanguageKeyLocalTranslation('facility_reserve_step_1') }}</span>
                    </div>
                    <div class="col-12 col-lg-6 step" :class="{ active: currentStep === 2 }">
                        <span>{{ getLanguageKeyLocalTranslation('facility_reserve_step_2') }}</span>
                    </div>
                </div>
            </div>

            <!-- Success/Error Alert -->
            <div v-if="showAlert" class="alert mb-4" :class="alertType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                <div class="d-flex align-items-center">
                    <i :class="alertType === 'success' ? 'uil uil-check-circle' : 'uil uil-exclamation-triangle'" class="me-2"></i>
                    @{{ alertMessage }}
                    <button type="button" class="btn-close ms-auto" @click="showAlert = false"></button>
                </div>
            </div>

            <!-- Step 1: Time Slot Selection -->
            <div v-show="currentStep === 1">
                <div class="card">
                    <div class="card-body p-2 p-sm-8">
                        <div class="time-slot-legend">
                            <div class="legend-item">
                                <div class="legend-color legend-available"></div>
                                <span>{{ getLanguageKeyLocalTranslation('visit_pages_available_slots') }}</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-fully-booked"></div>
                                <span>{{ getLanguageKeyLocalTranslation('visit_pages_fully_booked') }}</span>
                            </div>
                        </div>

                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Details + Confirmation -->
            <div v-show="currentStep === 2">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center p-2 p-sm-8">
                                <div>
                                    <button class="btn btn-sm btn-primary mb-2" @click="goBackToSlots()">
                                        <i class="uil uil-arrow-left"></i>
                                    </button>

                                    <h2 class="mb-0">{{ transOrDefault($facility, 'title') }}</h2>
                                    <small class="text-muted" v-if="selectedTimeSlot">
                                        @{{ formatDateTime(selectedTimeSlot.start) }}
                                    </small>
                                </div>
                            </div>

                            <div class="card-body">
                                <h2>{{ getLanguageKeyLocalTranslation('facility_reserve_confirmation_title') }}</h2>

                                <div class="mt-6" v-if="selectedTimeSlot">
                                    <form @submit.prevent="confirmReservation()">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <!-- Name -->
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">
                                                        <i class="uil uil-user"></i>
                                                    </span>
                                                    <input id="nameInput"
                                                        v-model.trim="reservationForm.name"
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.name }"
                                                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_name_input') }}">
                                                </div>
                                                <div v-if="errors.name" class="text-danger mb-3">@{{ errors.name }}</div>
                                                <div v-else class="mb-3"></div>

                                                <!-- Email -->
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">
                                                        <i class="uil uil-envelope"></i>
                                                    </span>
                                                    <input id="emailInput"
                                                        v-model.trim="reservationForm.email"
                                                        type="email"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.email }"
                                                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_email_input') }}">
                                                </div>
                                                <div v-if="errors.email" class="text-danger mb-3">@{{ errors.email }}</div>
                                                <div v-else class="mb-3"></div>

                                                <!-- Phone -->
                                                <div class="mb-2">
                                                    <input id="phoneInput"
                                                        v-model.trim="reservationForm.phone"
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.phone }"
                                                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_phone_input') }}">
                                                </div>
                                                <div v-if="errors.phone" class="text-danger mb-3">@{{ errors.phone }}</div>
                                                <div v-else class="mb-3"></div>

                                                <!-- Guests count -->
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">
                                                        <i class="uil uil-users-alt"></i>
                                                    </span>
                                                    <input id="guestsInput"
                                                        v-model.number="reservationForm.guests_count"
                                                        type="number"
                                                        min="1"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.guests_count }"
                                                        placeholder="{{ getLanguageKeyLocalTranslation('facility_reserve_guests_input') }}">
                                                </div>
                                                <div v-if="errors.guests_count" class="text-danger mb-3">@{{ errors.guests_count }}</div>
                                                <div v-else class="mb-3"></div>

                                                <!-- Message -->
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">
                                                        <i class="uil uil-comment-message"></i>
                                                    </span>
                                                    <textarea id="messageInput"
                                                        v-model.trim="reservationForm.message"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': errors.message }"
                                                        rows="4"
                                                        placeholder="{{ getLanguageKeyLocalTranslation('facility_reserve_message_input') }}"></textarea>
                                                </div>
                                                <div v-if="errors.message" class="text-danger mb-3">@{{ errors.message }}</div>
                                                <div v-else class="mb-3"></div>
                                            </div>

                                            <div class="col-md-6 text-center text-md-start mt-4 mt-md-0">
                                                <p><strong>{{ getLanguageKeyLocalTranslation('facility_reserve_selected_facility') }}:</strong> {{ transOrDefault($facility, 'title') }}</p>
                                                <p><strong>{{ getLanguageKeyLocalTranslation('visits_page_date_time') }}:</strong> @{{ formatDateTime(selectedTimeSlot.start) }}</p>

                                                <div class="d-flex justify-content-center justify-content-md-end">
                                                    <button type="submit" class="btn btn-primary" :disabled="reservationInProgress">
                                                        <span v-if="reservationInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                                        {{ getLanguageKeyLocalTranslation('visits_page_confirm_button') }}
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
window.facilityTimeSlots = [
    @foreach ($timeSlots as $timeSlot)
    {
        id: {{ $timeSlot->id }},
        starts_at: "{{ $timeSlot->starts_at }}",
        ends_at: "{{ $timeSlot->ends_at }}",
        remaining_capacity: "{{ $timeSlot->remaining_capacity }}",
        total_capacity: "{{ $timeSlot->capacity }}"
    },
    @endforeach
];
</script>
@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/calendar/main.min.js') }}"></script>
<script src='{{ URL::asset('assets/libs/calendar/locales-all.min.js') }}'></script>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<script>
const { createApp, nextTick } = Vue;

createApp({
    data() {
        return {
            currentStep: 1,
            calendar: null,
            selectedTimeSlot: null,
            reservationInProgress: false,
            iti: null,

            reservationForm: {
                name: '',
                email: '',
                phone: '',
                guests_count: 1,
                message: ''
            },

            errors: {},
            showAlert: false,
            alertType: 'success',
            alertMessage: ''
        }
    },
    methods: {
        initializeCalendar() {
            if (this.calendar) {
                this.calendar.destroy();
            }

            const timeSlots = window.facilityTimeSlots || [];

            const calendarEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                locale: '{{ app()->getLocale() }}',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next',
                    right: 'dayGridMonth,timeGridWeek'
                },
                height: 'auto',
                slotMinTime: '08:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,

                events: timeSlots.map(slot => {
                    let eventColor, eventTitle, eventClass, isClickable;

                    if (slot.remaining_capacity <= 0) {
                        eventColor = '#dc3545'; // Red
                        eventTitle = `{{ getLanguageKeyLocalTranslation("visit_pages_fully_booked") }} (${slot.total_capacity}/${slot.total_capacity})`;
                        eventClass = ['fully-booked-slot'];
                        isClickable = false;
                    } else {
                        eventColor = '#198754'; // Green
                        eventTitle = `{{ getLanguageKeyLocalTranslation("visit_pages_available_slots") }} (${slot.remaining_capacity}/${slot.total_capacity})`;
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

                eventClick: (info) => {
                    const extendedProps = info.event.extendedProps;

                    if (extendedProps.fully_booked) {
                        this.showErrorAlert('{{ getLanguageKeyLocalTranslation("visit_pages_insufficient_capacity") }}');
                        return false;
                    } else {
                        this.selectTimeSlot(info.event);
                    }
                },

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
            this.selectedTimeSlot = {
                id: event.id,
                start: event.start,
                end: event.end
            };

            this.currentStep = 2;

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

        async confirmReservation() {
            this.reservationInProgress = true;
            this.errors = {};
            this.showAlert = false;

            const fullPhoneNumber = this.iti ? this.iti.getNumber() : this.reservationForm.phone;

            try {
                const response = await fetch('{{ facilityRoute("reservations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.reservationForm.name.trim() || null,
                        email: this.reservationForm.email.trim() || null,
                        phone: fullPhoneNumber.trim() || null,
                        guests_count: this.reservationForm.guests_count || 1,
                        message: this.reservationForm.message.trim() || null,
                        facility_time_slot_id: this.selectedTimeSlot.id
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showSuccessAlert('{{ getLanguageKeyLocalTranslation('facility_reserve_success_message') }}');
                    this.resetReservation();
                } else {
                    if (result.errors) {
                        this.handleValidationErrors(result.errors);

                        if (result.errors.facility_time_slot_id) {
                            this.showErrorAlert(result.message || '{{ getLanguageKeyLocalTranslation('visit_pages_insufficient_capacity') }}');
                        }
                    } else {
                        this.showErrorAlert(result.message || '{{ getLanguageKeyLocalTranslation('facility_reserve_error_message') }}');
                    }
                }
            } catch (error) {
                this.showErrorAlert('{{ getLanguageKeyLocalTranslation('facility_reserve_error_message') }}');
            } finally {
                this.reservationInProgress = false;
            }
        },

        handleValidationErrors(errors) {
            this.errors = {};

            if (Array.isArray(errors)) {
                errors.forEach(error => {
                    this.errors[error.field] = error.message;
                });
            } else {
                Object.keys(errors).forEach(field => {
                    this.errors[field] = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                });
            }
        },

        showSuccessAlert(message) {
            this.alertType = 'success';
            this.alertMessage = message;
            this.showAlert = true;

            setTimeout(() => {
                this.showAlert = false;
            }, 5000);
        },

        showErrorAlert(message) {
            this.alertType = 'error';
            this.alertMessage = message;
            this.showAlert = true;

            setTimeout(() => {
                this.showAlert = false;
            }, 8000);
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('{{ app()->getLocale() }}');
        },

        resetReservation() {
            this.currentStep = 1;
            this.selectedTimeSlot = null;
            this.reservationForm = {
                name: '',
                email: '',
                phone: '',
                guests_count: 1,
                message: ''
            };
            this.errors = {};

            nextTick(() => {
                this.initializeCalendar();
            });
        },

        goBackToSlots() {
            this.currentStep = 1;
            this.selectedTimeSlot = null;

            this.errors = {};
            this.showAlert = false;

            this.$nextTick(() => {
                this.initializeCalendar();
            });
        }
    },

    mounted() {
        this.initializeCalendar();
    }
}).mount('#reservation-app');
</script>
@endsection
