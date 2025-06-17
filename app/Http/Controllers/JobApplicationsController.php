<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobApplications\StoreJobApplicationRequest;
use App\Http\Requests\JobApplications\ValidateJobApplicationStepRequest;
use App\Http\Requests\VisitBookings\StoreVisitBookingRequest;
use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Grade;
use App\Models\JobPosting;
use App\Models\Page;
use App\Models\Program;
use App\Models\VisitBooking;
use App\Models\VisitService;
use App\Models\VisitTimeSlot;
use App\Services\FileService;
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class JobApplicationsController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
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
        $application = $jobPosting->jobApplications()->create([
            'first_name' => $request->input('personal.first_name'),
            'last_name' => $request->input('personal.last_name'),
            'email' => $request->input('personal.email'),
            'phone' => $request->input('personal.phone'),
            'nationality' => $request->input('personal.nationality'),
            'address' => $request->input('personal.address'),
            'skills' => $request->input('skills'), 
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

        return response()->json([
                'status' => 'success',
                'message' => 'Job applciation step validated successfully!',
        ], 200);
    }
}
