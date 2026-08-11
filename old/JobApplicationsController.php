<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobApplications\StoreJobApplicationRequest;
use App\Http\Requests\JobApplications\ValidateJobApplicationStepRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Nationality;
use App\Services\FileService;
use App\Services\JobApplicationScoringService;
use App\Services\NotificationService;
use Illuminate\Support\Str;

class JobApplicationsController extends Controller
{
    protected $fileService;

    protected $notificationService;

    public function __construct(FileService $fileService, NotificationService $notificationService)
    {
        $this->fileService = $fileService;
        $this->notificationService = $notificationService;
    }

    public function validateApplication(ValidateJobApplicationStepRequest $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Job applciation step validated successfully!',
        ], 200);
    }

    public function storeApplication(StoreJobApplicationRequest $request, JobPosting $jobPosting)
    {
        return $this->store($request, $jobPosting);
    }

    public function storeGeneralApplication(StoreJobApplicationRequest $request)
    {
        return $this->store($request, null);
    }

    protected function store(StoreJobApplicationRequest $request, ?JobPosting $jobPosting = null)
    {
        $nationality = Nationality::where('code', Str::upper($request->input('personal.nationality')))->first();

        $application = JobApplication::create([
            'first_name' => $request->input('personal.first_name'),
            'last_name' => $request->input('personal.last_name'),
            'email' => $request->input('personal.email'),
            'phone' => $request->input('personal.phone'),
            'nationality' => $nationality->name ?? $request->input('personal.nationality'),
            'address' => $request->input('personal.address'),
            'skills' => $request->input('skills'),
            'job_posting_id' => $jobPosting?->id,
            'ai_score_status' => JobApplication::AI_SCORE_STATUS_PENDING,
        ]);

        foreach ($request->input('education') as $educationData) {
            $application->education()->create([
                'institution' => $educationData['institution'],
                'degree' => $educationData['degree'],
                'field_of_study' => $educationData['field_of_study'],
                'start_year' => $educationData['start_year'],
                'end_year' => $educationData['end_year'],
                'description' => $educationData['description'],
            ]);
        }

        // Store experience records
        foreach ($request->input('experience') as $experienceData) {
            $application->experiences()->create([
                'company_name' => $experienceData['company_name'],
                'job_title' => $experienceData['job_title'],
                'start_year' => $experienceData['start_year'],
                'end_year' => $experienceData['is_current'] === 'true' ? null : $experienceData['end_year'],
                'is_current' => $experienceData['is_current'] === 'true',
                'description' => $experienceData['description'],
            ]);
        }

        // Store language records
        foreach ($request->input('languages') as $languageData) {
            $application->languages()->create([
                'name' => $languageData['name'],
                'proficiency' => $languageData['proficiency'],
            ]);
        }

        // Handle CV file upload
        if ($request->hasFile('cv')) {
            $this->fileService->upload(
                $request->file('cv'),
                'App\\Models\\JobApplication',
                $application->id,
                true
            );
        }

        // Create notification
        $this->notificationService->createJobApplicationNotification($application);

        // Queue for scoring
        JobApplicationScoringService::queueForScoring($application);

        return response()->json([
            'status' => 'success',
            'message' => 'Job applciation step validated successfully!',
        ], 200);
    }
}
