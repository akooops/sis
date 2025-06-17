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
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class JobApplicationsController extends Controller
{
    public function validateApplication(ValidateJobApplicationStepRequest $request)
    {
        return response()->json([
                'status' => 'success',
                'message' => 'Job applciation step validated successfully!',
        ], 200);
    }

    public function storeApplication(StoreJobApplicationRequest $request, JobPosting $jobPosting)
    {
        return response()->json([
                'status' => 'success',
                'message' => 'Job applciation step validated successfully!',
        ], 200);
    }
}
