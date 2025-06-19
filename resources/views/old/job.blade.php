@extends('layouts.master')
@section('title', $job->getLocalTranslation('title'))
@section('description', $job->getLocalTranslation('description'))
@section('canonical', route('job', ['slug' => $job->slug]))
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.min.css"/>
<style>
    .step-indicator {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 4px 10px;
        opacity: 0.5;
        transition: opacity 0.3s;
    }
    
    .step.active {
        opacity: 1;
    }
    
    .step.completed {
        opacity: 0.8;
        color: #198754;
    }
    
    .step-number {
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .step.active .step-number {
        background: #2A5D91;
        color: white;
    }
    
    .step.completed .step-number {
        background: #198754;
        color: white;
    }
    
    .step-divider {
        width: 50px;
        height: 2px;
        background: #e9ecef;
        margin: 0 10px;
    }
    
    .step.completed + .step-divider {
        background: #198754;
    }

    .repeatable-section {
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1rem;
        position: relative;
    }
    
    .remove-section {
        position: absolute;
        top: 10px;
        right: 10px;
        color: #dc3545;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    .add-section-btn {
        border: 2px dashed #2A5D91;
        background: transparent;
        color: #2A5D91;
        padding: 1rem;
        border-radius: 0.375rem;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .add-section-btn:hover {
        background: rgba(13, 110, 253, 0.1);
    }

    .skills-container {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.75rem;
        min-height: 100px;
    }
    
    .skill-tag {
        display: inline-block;
        background: #2A5D91;
        color: white;
        padding: 0.15rem 0.5rem;
        border-radius: 1rem;
        margin: 0.25rem;
    }
    
    .skill-tag .remove-skill {
        margin-left: 0.5rem;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $job->thumbnailUrl }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$job->getLocalTranslation('title')}}
                </h1>
            </div>
        </div>
    </div>
</section>

<!-- Breadcrumb -->
<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('jobs')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_jobs_page_title')}}
                </a>
            </li>
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$job->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
   </div>
</section>

<!-- Main Content -->
<section class="wrapper bg-light" id="job-application-app">
    <div class="container py-12 py-md-5">
        <div class="row">
            <!-- Job Details Column -->
            <div class="col-lg-8">
                <div class="card shadow-lg" v-if="!showApplicationForm">
                    <div class="card-body">
                        <h2 class="h1 mb-3">{{getLanguageKeyLocalTranslation('job_description_title')}}</h2>
                        
                        @if($job->getLocalTranslation('description'))
                        <div class="mb-4">
                            <p>{{$job->getLocalTranslation('description')}}</p>
                        </div>
                        @endif

                        @if($job->getLocalTranslation('content'))
                        <div class="mb-6">
                            <x-markdown>
                                {{ $job->getLocalTranslation('content') }}
                            </x-markdown>
                        </div>
                        @endif
                        
                        <!-- Application Actions -->
                        <div class="d-flex align-items-center">
                            @if($job->application_deadline && $job->application_deadline < now())
                                <div class="alert alert-warning mb-0">
                                    <i class="uil uil-exclamation-triangle me-2"></i>
                                    {{getLanguageKeyLocalTranslation('job_application_expired')}}
                                </div>
                            @else
                                <button @click="showApplicationForm = true" class="btn btn-primary rounded-pill">
                                    <i class="uil uil-envelope me-2"></i>{{getLanguageKeyLocalTranslation('job_apply_now')}}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="card shadow-lg" v-if="showApplicationForm">
                    <div class="card-body">
                        <!-- Success/Error Alert -->
                        <div v-if="showAlert" class="alert mb-4" :class="alertType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                            <div class="d-flex align-items-center">
                                <i :class="alertType === 'success' ? 'uil uil-check-circle' : 'uil uil-exclamation-triangle'" class="me-2"></i>
                                @{{ alertMessage }}
                                <button type="button" class="btn-close ms-auto" @click="showAlert = false"></button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-8">
                            <h2 class="h1 mb-0">{{getLanguageKeyLocalTranslation('job_application_title')}}</h2>
                            <button @click="showApplicationForm = false" class="btn btn-sm btn-outline-secondary">
                                <i class="uil uil-times me-1"></i>{{getLanguageKeyLocalTranslation('job_close_form')}}
                            </button>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator align-items-center">
                            <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                                <div class="step-number">1</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_personal')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 1 }"></div>
                            <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                                <div class="step-number">2</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_education')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 2 }"></div>
                            <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
                                <div class="step-number">3</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_experience')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 3 }"></div>
                            <div class="step" :class="{ active: currentStep === 4, completed: currentStep > 4 }">
                                <div class="step-number">4</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_languages')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 4 }"></div>
                            <div class="step" :class="{ active: currentStep === 5, completed: currentStep > 5 }">
                                <div class="step-number">5</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_skills')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 5 }"></div>
                            <div class="step" :class="{ active: currentStep === 6, completed: currentStep > 6 }">
                                <div class="step-number">6</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_documents')}}</span>
                            </div>
                            <div class="step-divider" :class="{ completed: currentStep > 6 }"></div>
                            <div class="step" :class="{ active: currentStep === 7 }">
                                <div class="step-number">7</div>
                                <span>{{getLanguageKeyLocalTranslation('job_step_review')}}</span>
                            </div>
                        </div>

                        <!-- Step 1: Personal Information -->
                        <div v-if="currentStep === 1">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_personal_info')}}</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input v-model.trim="applicationForm.personal.first_name" 
                                           type="text" 
                                           class="form-control"
                                           :class="{ 'is-invalid': errors['personal.first_name'] }"
                                           placeholder="{{getLanguageKeyLocalTranslation('job_first_name')}}">
                                    <div v-if="errors['personal.first_name']" class="invalid-feedback">@{{ errors['personal.first_name'] }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input v-model.trim="applicationForm.personal.last_name" 
                                           type="text" 
                                           class="form-control"
                                           :class="{ 'is-invalid': errors['personal.last_name'] }"
                                           placeholder="{{getLanguageKeyLocalTranslation('job_last_name')}}">
                                    <div v-if="errors['personal.last_name']" class="invalid-feedback">@{{ errors['personal.last_name'] }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input v-model.trim="applicationForm.personal.email" 
                                           type="email" 
                                           class="form-control"
                                           :class="{ 'is-invalid': errors['personal.email'] }"
                                           placeholder="{{getLanguageKeyLocalTranslation('job_email')}}">
                                    <div v-if="errors['personal.email']" class="invalid-feedback">@{{ errors['personal.email'] }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input id="phoneInput" 
                                           v-model.trim="applicationForm.personal.phone" 
                                           type="tel" 
                                           class="form-control"
                                           :class="{ 'is-invalid': errors['personal.phone'] }"
                                           placeholder="{{getLanguageKeyLocalTranslation('job_phone')}}">
                                    <div v-if="errors['personal.phone']" class="invalid-feedback">@{{ errors['personal.phone'] }}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <input v-model.trim="applicationForm.personal.nationality" 
                                           type="text" 
                                           class="form-control"
                                           :class="{ 'is-invalid': errors['personal.nationality'] }"
                                        placeholder="{{getLanguageKeyLocalTranslation('job_nationality')}}">
                                    <div v-if="errors['personal.nationality']" class="invalid-feedback">@{{ errors['personal.nationality'] }}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <textarea v-model.trim="applicationForm.personal.address" 
                                              class="form-control" 
                                              rows="3"
                                              :class="{ 'is-invalid': errors['personal.address'] }"
                                              placeholder="{{getLanguageKeyLocalTranslation('job_address')}}"></textarea>
                                    <div v-if="errors['personal.address']" class="invalid-feedback">@{{ errors['personal.address'] }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Education -->
                        <div v-if="currentStep === 2">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_education')}}</h3>
                            <div v-if="errors['education']" class="alert alert-danger mb-3">
                                @{{ errors['education'] }}
                            </div>
                            <div v-for="(education, index) in applicationForm.education" :key="index" class="repeatable-section">
                                <span v-if="applicationForm.education.length > 1" 
                                      @click="removeEducation(index)" 
                                      class="remove-section">
                                    <i class="uil uil-times"></i>
                                </span>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="education.institution" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`education.${index}.institution`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_institution')}}">
                                        <div v-if="errors[`education.${index}.institution`]" class="invalid-feedback">@{{ errors[`education.${index}.institution`] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="education.degree" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`education.${index}.degree`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_degree')}}">
                                        <div v-if="errors[`education.${index}.degree`]" class="invalid-feedback">@{{ errors[`education.${index}.degree`] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="education.field_of_study" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`education.${index}.field_of_study`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_field_study')}}">
                                        <div v-if="errors[`education.${index}.field_of_study`]" class="invalid-feedback">@{{ errors[`education.${index}.field_of_study`] }}</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input v-model="education.start_year" 
                                               type="number" 
                                               min="1950" 
                                               max="2030" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`education.${index}.start_year`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_start_year')}}">
                                        <div v-if="errors[`education.${index}.start_year`]" class="invalid-feedback">@{{ errors[`education.${index}.start_year`] }}</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input v-model="education.end_year" 
                                               type="number" 
                                               min="1950" 
                                               max="2030" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`education.${index}.end_year`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_end_year')}}">
                                        <div v-if="errors[`education.${index}.end_year`]" class="invalid-feedback">@{{ errors[`education.${index}.end_year`] }}</div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <textarea v-model.trim="education.description" 
                                                  class="form-control" 
                                                  rows="2"
                                                  :class="{ 'is-invalid': errors[`education.${index}.description`] }"
                                                  placeholder="{{getLanguageKeyLocalTranslation('job_description')}}"></textarea>
                                        <div v-if="errors[`education.${index}.description`]" class="invalid-feedback">@{{ errors[`education.${index}.description`] }}</div>
                                    </div>
                                </div>
                            </div>
                            <button @click="addEducation" class="add-section-btn">
                                <i class="uil uil-plus me-2"></i>{{getLanguageKeyLocalTranslation('job_add_education')}}
                            </button>
                        </div>

                        <!-- Step 3: Work Experience -->
                        <div v-if="currentStep === 3">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_work_experience')}}</h3>
                            <div v-if="errors['experience']" class="alert alert-danger mb-3">
                                @{{ errors['experience'] }}
                            </div>
                            <div v-for="(experience, index) in applicationForm.experience" :key="index" class="repeatable-section">
                                <span v-if="applicationForm.experience.length > 1" 
                                      @click="removeExperience(index)" 
                                      class="remove-section">
                                    <i class="uil uil-times"></i>
                                </span>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="experience.company_name" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`experience.${index}.company_name`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_company_name')}}">
                                        <div v-if="errors[`experience.${index}.company_name`]" class="invalid-feedback">@{{ errors[`experience.${index}.company_name`] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="experience.job_title" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`experience.${index}.job_title`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_job_title')}}">
                                        <div v-if="errors[`experience.${index}.job_title`]" class="invalid-feedback">@{{ errors[`experience.${index}.job_title`] }}</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input v-model="experience.start_year" 
                                               type="number" 
                                               min="1950" 
                                               max="2030" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`experience.${index}.start_year`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_start_year')}}">
                                        <div v-if="errors[`experience.${index}.start_year`]" class="invalid-feedback">@{{ errors[`experience.${index}.start_year`] }}</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <input v-model="experience.end_year" 
                                               type="number" 
                                               min="1950" 
                                               max="2030" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`experience.${index}.end_year`] }"
                                               :disabled="experience.is_current"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_end_year')}}">
                                        <div v-if="errors[`experience.${index}.end_year`]" class="invalid-feedback">@{{ errors[`experience.${index}.end_year`] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input v-model="experience.is_current" 
                                                   type="checkbox" 
                                                   class="form-check-input"
                                                   @change="handleCurrentJobChange(experience)">
                                            <label class="form-check-label">
                                                {{getLanguageKeyLocalTranslation('job_current_job')}}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <textarea v-model.trim="experience.description" 
                                                  class="form-control" 
                                                  rows="3"
                                                  :class="{ 'is-invalid': errors[`experience.${index}.description`] }"
                                                  placeholder="{{getLanguageKeyLocalTranslation('job_job_description')}}"></textarea>
                                        <div v-if="errors[`experience.${index}.description`]" class="invalid-feedback">@{{ errors[`experience.${index}.description`] }}</div>
                                    </div>
                                </div>
                            </div>
                            <button @click="addExperience" class="add-section-btn">
                                <i class="uil uil-plus me-2"></i>{{getLanguageKeyLocalTranslation('job_add_experience')}}
                            </button>
                        </div>

                        <!-- Step 4: Languages -->
                        <div v-if="currentStep === 4">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_languages')}}</h3>
                            <div v-if="errors['languages']" class="alert alert-danger mb-3">
                                @{{ errors['languages'] }}
                            </div>
                            <div v-for="(language, index) in applicationForm.languages" :key="index" class="repeatable-section">
                                <span v-if="applicationForm.languages.length > 1" 
                                      @click="removeLanguage(index)" 
                                      class="remove-section">
                                    <i class="uil uil-times"></i>
                                </span>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input v-model.trim="language.name" 
                                               type="text" 
                                               class="form-control"
                                               :class="{ 'is-invalid': errors[`languages.${index}.name`] }"
                                               placeholder="{{getLanguageKeyLocalTranslation('job_language_name')}}">
                                        <div v-if="errors[`languages.${index}.name`]" class="invalid-feedback">@{{ errors[`languages.${index}.name`] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <select v-model="language.proficiency" 
                                                class="form-control"
                                                :class="{ 'is-invalid': errors[`languages.${index}.proficiency`] }">
                                            <option value="">{{getLanguageKeyLocalTranslation('job_select_proficiency')}}</option>
                                            <option value="basic">{{getLanguageKeyLocalTranslation('job_basic')}}</option>
                                            <option value="intermediate">{{getLanguageKeyLocalTranslation('job_intermediate')}}</option>
                                            <option value="advanced">{{getLanguageKeyLocalTranslation('job_advanced')}}</option>
                                            <option value="native">{{getLanguageKeyLocalTranslation('job_native')}}</option>
                                        </select>
                                        <div v-if="errors[`languages.${index}.proficiency`]" class="invalid-feedback">@{{ errors[`languages.${index}.proficiency`] }}</div>
                                    </div>
                                </div>
                            </div>
                            <button @click="addLanguage" class="add-section-btn">
                                <i class="uil uil-plus me-2"></i>{{getLanguageKeyLocalTranslation('job_add_language')}}
                            </button>
                        </div>

                        <!-- Step 5: Skills -->
                        <div v-if="currentStep === 5">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_skills')}}</h3>
                            <div v-if="errors['skills']" class="alert alert-danger mb-3">
                                @{{ errors['skills'] }}
                            </div>
                            <div class="mb-3">
                                <input v-model="newSkill" 
                                       @keyup.enter="addSkill" 
                                       type="text" 
                                       class="form-control"
                                       placeholder="{{getLanguageKeyLocalTranslation('job_add_skills')}}">
                            </div>
                            <div class="skills-container">
                                <span v-for="(skill, index) in applicationForm.skills" :key="index" class="skill-tag">
                                    @{{ skill }}
                                    <span @click="removeSkill(index)" class="remove-skill">&times;</span>
                                </span>
                                <div v-if="applicationForm.skills.length === 0" class="text-muted">
                                    {{getLanguageKeyLocalTranslation('job_no_skills')}}
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: Documents -->
                        <div v-if="currentStep === 6">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_documents')}}</h3>
                            
                            <!-- CV Upload - Simple Bootstrap Input -->
                            <div class="mb-4">
                                <label for="cvInput" class="form-label">
                                    {{getLanguageKeyLocalTranslation('job_cv_required')}} 
                                    <span class="text-danger">*</span>
                                </label>
                                <input id="cvInput"
                                    ref="cvInput" 
                                    @change="handleFileUpload($event, 'cv')" 
                                    type="file" 
                                    accept=".pdf,.doc,.docx" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errors['cv'] }">
                                <div class="form-text">{{getLanguageKeyLocalTranslation('job_cv_formats')}}</div>
                                <div v-if="errors['cv']" class="invalid-feedback">@{{ errors['cv'] }}</div>
                                
                                <!-- Display uploaded file -->
                                <div v-if="applicationForm.documents.cv" class="mt-2">
                                    <div class="alert alert-success d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="uil uil-file-alt me-2"></i>
                                            @{{ applicationForm.documents.cv.name }}
                                        </div>
                                        <button @click="removeFile('cv')" type="button" class="btn btn-sm btn-outline-danger">
                                            <i class="uil uil-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Step 7: Review & Submit -->
                        <div v-if="currentStep === 7">
                            <h3 class="mb-4">{{getLanguageKeyLocalTranslation('job_review_submit')}}</h3>
                            
                            <!-- Review Summary -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>{{getLanguageKeyLocalTranslation('job_personal_info')}}</h5>
                                    <p><strong>{{getLanguageKeyLocalTranslation('job_name')}}:</strong> @{{ applicationForm.personal.first_name }} @{{ applicationForm.personal.last_name }}</p>
                                    <p><strong>{{getLanguageKeyLocalTranslation('job_email')}}:</strong> @{{ applicationForm.personal.email }}</p>
                                    <p><strong>{{getLanguageKeyLocalTranslation('job_phone')}}:</strong> @{{ applicationForm.personal.phone }}</p>
                                    
                                    <h5 class="mt-4">{{getLanguageKeyLocalTranslation('job_education')}}</h5>
                                    <div v-for="education in applicationForm.education" class="mb-2">
                                        <strong>@{{ education.degree }}</strong> - @{{ education.institution }}
                                        <br><small class="text-muted">@{{ education.start_year }} - @{{ education.end_year || 'Present' }}</small>
                                    </div>
                                    
                                    <h5 class="mt-4">{{getLanguageKeyLocalTranslation('job_work_experience')}}</h5>
                                    <div v-for="experience in applicationForm.experience" class="mb-2">
                                        <strong>@{{ experience.job_title }}</strong> - @{{ experience.company_name }}
                                        <br><small class="text-muted">@{{ experience.start_year }} - @{{ experience.end_year || 'Present' }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>{{getLanguageKeyLocalTranslation('job_languages')}}</h5>
                                    <div v-for="language in applicationForm.languages" class="mb-1">
                                        <strong>@{{ language.name }}</strong> - @{{ language.proficiency }}
                                    </div>
                                    
                                    <h5 class="mt-4">{{getLanguageKeyLocalTranslation('job_skills')}}</h5>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span v-for="skill in applicationForm.skills" class="badge bg-primary">@{{ skill }}</span>
                                    </div>
                                    
                                    <h5 class="mt-4">{{getLanguageKeyLocalTranslation('job_documents')}}</h5>
                                    <p v-if="applicationForm.documents.cv"><i class="uil uil-file-alt me-1"></i>CV: @{{ applicationForm.documents.cv.name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <button v-if="currentStep > 1" 
                                    @click="currentStep--" 
                                    class="btn btn-outline-primary">
                                <i class="uil uil-arrow-left me-1"></i>{{getLanguageKeyLocalTranslation('job_previous')}}
                            </button>
                            <div v-else></div>
                            
                            <button v-if="currentStep < 7" 
                                    @click="nextStep" 
                                    class="btn btn-primary">
                                {{getLanguageKeyLocalTranslation('job_next')}}<i class="uil uil-arrow-right ms-1"></i>
                            </button>
                            <button v-else 
                                    @click="submitApplication" 
                                    :disabled="applicationInProgress"
                                    class="btn btn-primary">
                                <span v-if="applicationInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                {{getLanguageKeyLocalTranslation('job_submit_application')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-lg mb-4">
                    <div class="card-body">
                        <h3 class="h3 mb-4">{{getLanguageKeyLocalTranslation('job_details_title')}}</h3>
                        <ul class="list-unstyled mb-0">
                            @if($job->required_years_of_experience)
                            <li class="mb-3">
                                <i class="uil uil-clock text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_experience_required')}}:</strong>
                                <span class="ms-2">{{ $job->required_years_of_experience }}+ {{getLanguageKeyLocalTranslation('job_years')}}</span>
                            </li>
                            @endif

                            <li class="mb-3">
                                <i class="uil uil-briefcase text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_employment_type')}}:</strong>
                                <span class="ms-2">
                                    @if($job->employment_type == 'full_time')
                                        {{getLanguageKeyLocalTranslation('jobs_full_time')}}
                                    @elseif($job->employment_type == 'part_time')
                                        {{getLanguageKeyLocalTranslation('jobs_part_time')}}
                                    @else
                                        {{getLanguageKeyLocalTranslation('jobs_internship')}}
                                    @endif
                                </span>
                            </li>

                            <li class="mb-3">
                                <i class="uil uil-{{ $job->is_remote ? 'laptop' : 'building' }} text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_work_type')}}:</strong>
                                <span class="ms-2">
                                    @if($job->is_remote)
                                        {{getLanguageKeyLocalTranslation('jobs_remote')}}
                                    @else
                                        {{getLanguageKeyLocalTranslation('jobs_onsite')}}
                                    @endif
                                </span>
                            </li>
                            
                            <li class="mb-3">
                                <i class="uil uil-users-alt text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_positions_available')}}:</strong>
                                <span class="ms-2">{{ $job->number_of_positions }}</span>
                            </li>

                            @if($job->getLocalTranslation('required_skills'))
                            <li class="mb-3">
                                <i class="uil uil-star text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_skills_title')}}:</strong>
                                <div class="mt-2">
                                    @php
                                        $skills = explode(',', $job->getLocalTranslation('required_skills'));
                                    @endphp
                                    @foreach(array_filter(array_map('trim', $skills)) as $skill)
                                        <span class="badge bg-light text-dark me-1 mb-1">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </li>
                            @endif

                            @if($job->application_deadline)
                            <li class="mb-3">
                                <i class="uil uil-calendar-alt text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_application_deadline')}}:</strong>
                                <span class="ms-2">{{ $job->application_deadline }}</span>
                            </li>
                            @endif

                            <li class="mb-0">
                                <i class="uil uil-calendar-plus text-primary me-2"></i>
                                <strong>{{getLanguageKeyLocalTranslation('job_posted_date')}}:</strong>
                                <span class="ms-2">{{ $job->created_at }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            showApplicationForm: false,
            currentStep: 1,
            applicationInProgress: false,
            iti: null,
            newSkill: '',
            
            applicationForm: {
                job_posting_id: {{ $job->id }},
                personal: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    nationality: '',
                    date_of_birth: '',
                    address: ''
                },
                education: [{
                    institution: '',
                    degree: '',
                    field_of_study: '',
                    start_year: '',
                    end_year: '',
                    description: ''
                }],
                experience: [{
                    company_name: '',
                    job_title: '',
                    start_year: '',
                    end_year: '',
                    is_current: false,
                    description: ''
                }],
                languages: [{
                    name: '',
                    proficiency: ''
                }],
                skills: [],
                documents: {
                    cv: null
                }
            },
            
            errors: {},
            showAlert: false,
            alertType: 'success',
            alertMessage: ''
        }
    },
    
    watch: {
        showApplicationForm(newVal) {
            if (newVal) {
                this.$nextTick(() => {
                    this.initIntlTelInput();
                });
            }
        }
    },
    
    methods: {
        async nextStep() {
            let isValid = false;
            
            if (this.currentStep === 6) {
                isValid = await this.validateDocumentsStep();
            } else {
                isValid = await this.validateCurrentStep();
            }
            
            if (isValid && this.currentStep < 7) {
                this.currentStep++;
                
                if (this.currentStep === 1) {
                    this.$nextTick(() => {
                        this.initIntlTelInput();
                    });
                }
            }
        },

        async validateCurrentStep() {
            if (this.currentStep === 7) {
                return true;
            }
            
            try {
                const stepData = this.getCurrentStepData();
                const isValid = await this.validateStepWithServer(stepData);
                return isValid;
            } catch (error) {
                console.error('Step validation error:', error);
                this.showErrorAlert('Validation error occurred');
                return false;
            }
        },

        async validateStepWithServer(stepData) {
            try {
                const response = await fetch('{{ route("job-applications.validate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(stepData)
                });

                const result = await response.json();
                
                if (response.ok && result.status === 'success') {
                    this.errors = {};
                    return true;
                } else {
                    if (result.errors) {
                        this.handleValidationErrors(result.errors);
                    }
                    this.showErrorAlert(result.message || 'Please fix the errors before continuing');
                    return false;
                }
            } catch (error) {
                console.error('Network error:', error);
                this.showErrorAlert('Network error. Please check your connection.');
                return false;
            }
        },

        getCurrentStepData() {
            const baseData = { step: this.currentStep };
            
            switch (this.currentStep) {
                case 1:
                    return {
                        ...baseData,
                        personal: {
                            ...this.applicationForm.personal,
                            phone: this.iti ? this.iti.getNumber() : this.applicationForm.personal.phone
                        }
                    };
                case 2:
                    return {
                        ...baseData,
                        education: this.applicationForm.education
                    };
                case 3:
                    return {
                        ...baseData,
                        experience: this.applicationForm.experience
                    };
                case 4:
                    return {
                        ...baseData,
                        languages: this.applicationForm.languages
                    };
                case 5:
                    return {
                        ...baseData,
                        skills: this.applicationForm.skills
                    };
                case 6:
                    return this.getCurrentStepFormData();
                default:
                    return baseData;
            }
        },

        getCurrentStepFormData() {
            const formData = new FormData();
            formData.append('step', this.currentStep);
            
            if (this.applicationForm.documents.cv) {
                formData.append('cv', this.applicationForm.documents.cv);
            }
            
            return formData;
        },

        async validateDocumentsStep() {
            try {
                const formData = this.getCurrentStepFormData();
                
                const response = await fetch('{{ route("job-applications.validate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.errors = {};
                    return true;
                } else {
                    if (result.errors) {
                        this.handleValidationErrors(result.errors);
                    }
                    this.showErrorAlert('Please fix the document errors before continuing');
                    return false;
                }
            } catch (error) {
                console.error('Document validation error:', error);
                this.showErrorAlert('Error validating documents');
                return false;
            }
        },

        handleValidationErrors(errors) {
            this.errors = {};
            
            Object.keys(errors).forEach(field => {
                const errorMessage = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                this.errors[field] = errorMessage;
            });
        },

        initIntlTelInput() {
            this.$nextTick(() => {
                const input = document.getElementById('phoneInput');
                if (!input) return;

                if (this.iti) {
                    this.iti.destroy();
                    this.iti = null;
                }

                this.iti = window.intlTelInput(input, {
                    initialCountry: "sa",
                    preferredCountries: ["sa", "ae", "eg"],
                    separateDialCode: true,
                    formatOnDisplay: true,
                    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
                });
            });
        },

        addEducation() {
            this.applicationForm.education.push({
                institution: '',
                degree: '',
                field_of_study: '',
                start_year: '',
                end_year: '',
                description: ''
            });
        },
        
        removeEducation(index) {
            if (this.applicationForm.education.length > 1) {
                this.applicationForm.education.splice(index, 1);
            }
        },

        addExperience() {
            this.applicationForm.experience.push({
                company_name: '',
                job_title: '',
                start_year: '',
                end_year: '',
                is_current: false,
                description: ''
            });
        },
        
        removeExperience(index) {
            if (this.applicationForm.experience.length > 1) {
                this.applicationForm.experience.splice(index, 1);
            }
        },

        handleCurrentJobChange(experience) {
            if (experience.is_current) {
                experience.end_year = '';
            }
        },

        addLanguage() {
            this.applicationForm.languages.push({
                name: '',
                proficiency: ''
            });
        },
        
        removeLanguage(index) {
            if (this.applicationForm.languages.length > 1) {
                this.applicationForm.languages.splice(index, 1);
            }
        },

        addSkill() {
            if (this.newSkill.trim() && !this.applicationForm.skills.includes(this.newSkill.trim())) {
                this.applicationForm.skills.push(this.newSkill.trim());
                this.newSkill = '';
            }
        },
        
        removeSkill(index) {
            this.applicationForm.skills.splice(index, 1);
        },

        handleFileUpload(event, type) {
            const files = event.target.files;
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (type === 'cv' && files.length > 0) {
                const file = files[0];
                
                // File size validation
                if (file.size > maxSize) {
                    this.showErrorAlert('File size must be less than 5MB');
                    this.$refs.cvInput.value = ''; // Clear the input
                    return;
                }
                
                // File type validation
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    this.showErrorAlert('CV must be PDF, DOC, or DOCX format');
                    this.$refs.cvInput.value = ''; // Clear the input
                    return;
                }
                
                // Clear any existing errors
                if (this.errors['cv']) {
                    delete this.errors['cv'];
                }
                
                this.applicationForm.documents.cv = file;
            }
        },

        removeFile(type) {
            if (type === 'cv') {
                this.applicationForm.documents.cv = null;
                this.$refs.cvInput.value = ''; // Clear the file input
            }
        },

        async submitApplication() {
            this.applicationInProgress = true;
            this.errors = {};
            this.showAlert = false;
            
            try {
                const formData = new FormData();
                
                // Add job posting ID and form data
                formData.append('job_posting_id', this.applicationForm.job_posting_id);
                
                // Personal Information
                Object.keys(this.applicationForm.personal).forEach(key => {
                    if (key === 'phone' && this.iti) {
                        formData.append(`personal[${key}]`, this.iti.getNumber());
                    } else {
                        formData.append(`personal[${key}]`, this.applicationForm.personal[key] || '');
                    }
                });
                
                // Education
                this.applicationForm.education.forEach((education, index) => {
                    Object.keys(education).forEach(key => {
                        formData.append(`education[${index}][${key}]`, education[key] || '');
                    });
                });
                
                // Experience
                this.applicationForm.experience.forEach((experience, index) => {
                    Object.keys(experience).forEach(key => {
                        if (key === 'is_current') {
                            // Always send boolean true/false, never empty
                            formData.append(`experience[${index}][${key}]`, experience[key] ? 'true' : 'false');
                        } else {
                            formData.append(`experience[${index}][${key}]`, experience[key] || '');
                        }
                    });
                });

                // Languages
                this.applicationForm.languages.forEach((language, index) => {
                    Object.keys(language).forEach(key => {
                        formData.append(`languages[${index}][${key}]`, language[key] || '');
                    });
                });
                
                // Skills
                this.applicationForm.skills.forEach((skill, index) => {
                    formData.append(`skills[${index}]`, skill);
                });
                
                // Documents
                if (this.applicationForm.documents.cv) {
                    formData.append('cv', this.applicationForm.documents.cv);
                }
                
                // Submit to dedicated storage route
                const response = await fetch('{{ route("job-applications.store", $job->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.status === 'success') {                    
                    // Then show success message on job description page
                    this.showSuccessAlert('Application submitted successfully!');
                    
                    // Reset form data
                    this.resetForm();
                } else {
                    if (result.errors) {
                        this.handleValidationErrors(result.errors);
                        this.showErrorAlert(result.message || 'Error submitting application');
                    } else {
                        this.showErrorAlert(result.message || 'Error submitting application');
                    }
                }
            } catch (error) {
                console.error('Application error:', error);
                this.showErrorAlert('Error submitting application');
            } finally {
                this.applicationInProgress = false;
            }
        },

        navigateToFirstErrorStep() {
            const errorFields = Object.keys(this.errors);
            if (errorFields.length === 0) return;
            
            let targetStep = 1;
            
            for (const field of errorFields) {
                if (field.includes('personal')) targetStep = Math.min(targetStep, 1);
                else if (field.includes('education')) targetStep = Math.min(targetStep, 2);
                else if (field.includes('experience')) targetStep = Math.min(targetStep, 3);
                else if (field.includes('languages')) targetStep = Math.min(targetStep, 4);
                else if (field.includes('skills')) targetStep = Math.min(targetStep, 5);
                else if (field.includes('cv')) targetStep = Math.min(targetStep, 6);
            }
            
            this.currentStep = targetStep;
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

        resetForm() {
            this.currentStep = 1;
            
            this.$nextTick(() => {
                this.initIntlTelInput();
            });

            this.applicationForm = {
                job_posting_id: {{ $job->id }},
                personal: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    nationality: '',
                    date_of_birth: '',
                    address: ''
                },
                education: [{
                    institution: '',
                    degree: '',
                    field_of_study: '',
                    start_year: '',
                    end_year: '',
                    description: ''
                }],
                experience: [{
                    company_name: '',
                    job_title: '',
                    start_year: '',
                    end_year: '',
                    is_current: false,
                    description: ''
                }],
                languages: [{
                    name: '',
                    proficiency: ''
                }],
                skills: [],
                documents: {
                    cv: null
                }
            };
            this.errors = {};
        }
    }
}).mount('#job-application-app');
</script>
@endsection
