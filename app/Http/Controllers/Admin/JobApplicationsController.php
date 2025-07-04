<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Language;
use Illuminate\Http\Request;

class JobApplicationsController extends Controller
{
    public function index(Request $request, JobPosting $jobPosting)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $jobApplications = JobApplication::with('cv')->where('job_posting_id', $jobPosting->id)->latest();

        if ($search) {
            $jobApplications->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('nationality', 'like', '%' . $search . '%')
                      ->orWhere('address', 'like', '%' . $search . '%')
                      
                      // Search in skills (comma-separated)
                      ->orWhere('skills', 'like', '%' . $search . '%')
                      
                      // Search by AI score if numeric
                      ->when(is_numeric($search), function($q) use ($search) {
                          $q->orWhere('ai_score', $search);
                      })
                      
                      // Search in education relationship
                      ->orWhereHas('education', function($eduQuery) use ($search) {
                          $eduQuery->where('institution', 'like', '%' . $search . '%')
                                   ->orWhere('degree', 'like', '%' . $search . '%')
                                   ->orWhere('field_of_study', 'like', '%' . $search . '%')
                                   ->orWhere('description', 'like', '%' . $search . '%');
                      })
                      
                      // Search in experience relationship
                      ->orWhereHas('experiences', function($expQuery) use ($search) {
                          $expQuery->where('company_name', 'like', '%' . $search . '%')
                                   ->orWhere('job_title', 'like', '%' . $search . '%')
                                   ->orWhere('description', 'like', '%' . $search . '%');
                      })
                      
                      // Search in languages relationship
                      ->orWhereHas('languages', function($langQuery) use ($search) {
                          $langQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('proficiency', 'like', '%' . $search . '%');
                      });
            });
        }

        $jobApplications = $jobApplications->paginate($perPage, ['*'], 'application', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'applications' => $jobApplications->items(),
                'pagination' => $this->indexService->handlePagination($jobApplications)
            ]);
        }

        return inertia('JobApplications/Index', [
            'jobPosting' => $jobPosting,
        ]); 
    }

    /**
     * Display the specified job application
     */
    public function show(JobApplication $jobApplication)
    {
        $jobApplication->load(['jobPosting', 'education', 'experiences', 'languages', 'cv']);

        return inertia('JobApplications/Show', [
            'jobApplication' => $jobApplication,
        ]);
    }

    /**
     * Remove the specified job application from storage
     */
    public function destroy(JobApplication $jobApplication)
    {
        $jobApplication->delete();

        return redirect()->back()
                        ->with('success', 'Job application deleted successfully');
    }

    public function export(Request $request, JobPosting $jobPosting)
    {
        $jobApplications = JobApplication::where('job_posting_id', $jobPosting->id);

        $search = $request->query('search');

        if ($search) {
            $jobApplications->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('nationality', 'like', '%' . $search . '%')
                      ->orWhere('address', 'like', '%' . $search . '%')
                      
                      // Search in skills (comma-separated)
                      ->orWhere('skills', 'like', '%' . $search . '%')
                      
                      // Search by AI score if numeric
                      ->when(is_numeric($search), function($q) use ($search) {
                          $q->orWhere('ai_score', $search);
                      })
                      
                      // Search in education relationship
                      ->orWhereHas('education', function($eduQuery) use ($search) {
                          $eduQuery->where('institution', 'like', '%' . $search . '%')
                                   ->orWhere('degree', 'like', '%' . $search . '%')
                                   ->orWhere('field_of_study', 'like', '%' . $search . '%')
                                   ->orWhere('description', 'like', '%' . $search . '%');
                      })
                      
                      // Search in experience relationship
                      ->orWhereHas('experiences', function($expQuery) use ($search) {
                          $expQuery->where('company_name', 'like', '%' . $search . '%')
                                   ->orWhere('job_title', 'like', '%' . $search . '%')
                                   ->orWhere('description', 'like', '%' . $search . '%');
                      })
                      
                      // Search in languages relationship
                      ->orWhereHas('languages', function($langQuery) use ($search) {
                          $langQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('proficiency', 'like', '%' . $search . '%');
                      });
            });
        }

        $jobApplications = $jobApplications->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="job_applications_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($jobApplications) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Phone', 'Nationality', 'Skills', 
                'Education', 'Experience', 'Languages', 'AI Score', 'Applied Date'
            ]);

            foreach ($jobApplications as $app) {
                fputcsv($file, [
                    $app->id,
                    $app->first_name . ' ' . $app->last_name,
                    $app->email,
                    $app->phone,
                    $app->nationality,
                    $app->skills,
                    $app->education->pluck('institution')->implode('; '),
                    $app->experiences->pluck('company_name')->implode('; '),
                    $app->languages->pluck('name')->implode('; '),
                    $app->ai_score,
                    $app->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
