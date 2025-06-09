@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('visits'))
@section('css')
<link href="{{ URL::asset('assets/libs/calendar/main.min.css')}}" rel="stylesheet" type="text/css" />
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
                <div class="card">
                    <div class="card-body">

                        <h2>{{getLanguageKeyLocalTranslation('visits_page_confirmation_title')}}</h2>

                        <div v-if="selectedTimeSlot">
                            <div class="row">
                                <div class="col-md-6">
                                    ffgfd
                                </div>

                                <div class="col-md-6">
                                    <p><strong>Service:</strong> @{{ selectedService.name }}</p>
                                    <p><strong>Date & Time:</strong> @{{ formatDateTime(selectedTimeSlot.start) }}</p>
                                    <p><strong>Visitors:</strong> @{{ selectedService.visitorCount }}</p>
                                    <button class="btn btn-success" :disabled="bookingInProgress">
                                        <span v-if="bookingInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                        {{getLanguageKeyLocalTranslation('booking_confirm_button') ?? 'Confirm Booking'}}
                                    </button>
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
            @foreach ($visitService->visitTimeSlots as $timeSlot)
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

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<script>
const { createApp } = Vue;

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
            bookingInProgress: false
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
                availableSpots: event.extendedProps.availableSpots
            };
            
            this.currentStep = 3;
        },
        
        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('{{app()->getLocale()}}');
        },
        
        resetBooking() {
            this.currentStep = 1;
            this.selectedService = { id: null, name: '', visitorCount: 1, capacity: 0 };
            this.selectedTimeSlot = null;
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
