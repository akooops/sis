<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobApplicationsController extends Controller
{
    public function index(Request $request, JobPosting $jobPosting)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $jobApplications = JobApplication::with(['cv', 'education', 'experiences', 'languages'])
            ->where('job_posting_id', $jobPosting->id)
            ->latest();

        // Apply filters
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

        // Apply education filters
        if ($request->filled('education_institution')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('institution', $request->education_institution);
            });
        }

        if ($request->filled('education_degree')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('degree', $request->education_degree);
            });
        }

        if ($request->filled('education_field')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('field_of_study', $request->education_field);
            });
        }

        if ($request->filled('education_start_year')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('start_year', '>=', $request->education_start_year);
            });
        }

        if ($request->filled('education_end_year')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('end_year', '<=', $request->education_end_year);
            });
        }

        // Apply work experience filters
        if ($request->filled('work_company')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('company_name', $request->work_company);
            });
        }

        if ($request->filled('work_title')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('job_title', $request->work_title);
            });
        }

        if ($request->filled('work_start_year')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('start_year', '>=', $request->work_start_year);
            });
        }

        if ($request->filled('work_end_year')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('end_year', '<=', $request->work_end_year);
            });
        }

        // Apply language filters
        if ($request->filled('language_name')) {
            $jobApplications->whereHas('languages', function($query) use ($request) {
                $query->where('name', $request->language_name);
            });
        }

        if ($request->filled('language_proficiency')) {
            $jobApplications->whereHas('languages', function($query) use ($request) {
                $query->where('proficiency', $request->language_proficiency);
            });
        }

        // Apply nationality filter
        if ($request->filled('nationality')) {
            $jobApplications->where('nationality', $request->nationality);
        }

        // Apply skills filter
        if ($request->filled('skills')) {
            $jobApplications->where('skills', 'like', '%' . $request->skills . '%');
        }

        // Apply AI score filter
        if ($request->filled('ai_score_min')) {
            $jobApplications->where('ai_score', '>=', $request->ai_score_min);
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
     * Get education institutions for Select2 dropdown
     */
    public function getEducationInstitutions(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $institutions = DB::table('job_application_education')
            ->join('job_applications', 'job_application_education.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('institution', 'like', '%' . $search . '%')
            ->select('institution as text', 'institution as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $institutions->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get education degrees for Select2 dropdown
     */
    public function getEducationDegrees(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $degrees = DB::table('job_application_education')
            ->join('job_applications', 'job_application_education.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('degree', 'like', '%' . $search . '%')
            ->select('degree as text', 'degree as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $degrees->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get education fields for Select2 dropdown
     */
    public function getEducationFields(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $fields = DB::table('job_application_education')
            ->join('job_applications', 'job_application_education.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('field_of_study', 'like', '%' . $search . '%')
            ->select('field_of_study as text', 'field_of_study as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $fields->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get work companies for Select2 dropdown
     */
    public function getWorkCompanies(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $companies = DB::table('job_application_experiences')
            ->join('job_applications', 'job_application_experiences.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('company_name', 'like', '%' . $search . '%')
            ->select('company_name as text', 'company_name as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $companies->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get work job titles for Select2 dropdown
     */
    public function getWorkTitles(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $titles = DB::table('job_application_experiences')
            ->join('job_applications', 'job_application_experiences.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('job_title', 'like', '%' . $search . '%')
            ->select('job_title as text', 'job_title as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $titles->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get languages for Select2 dropdown
     */
    public function getLanguages(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $languages = DB::table('job_application_languages')
            ->join('job_applications', 'job_application_languages.job_application_id', '=', 'job_applications.id')
            ->where('job_applications.job_posting_id', $jobPosting->id)
            ->where('name', 'like', '%' . $search . '%')
            ->select('name as text', 'name as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $languages->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get nationalities for Select2 dropdown
     */
    public function getNationalities(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        $nationalities = DB::table('job_applications')
            ->where('job_posting_id', $jobPosting->id)
            ->where('nationality', 'like', '%' . $search . '%')
            ->select('nationality as text', 'nationality as id')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json([
            'results' => $nationalities->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text
                ];
            })
        ]);
    }

    /**
     * Get skills for Select2 dropdown
     */
    public function getSkills(Request $request, JobPosting $jobPosting)
    {
        $search = $request->get('search', '');
        
        // Get all skills from job applications, split by comma and flatten
        $skillsData = DB::table('job_applications')
            ->where('job_posting_id', $jobPosting->id)
            ->whereNotNull('skills')
            ->where('skills', '!=', '')
            ->pluck('skills');

        $allSkills = collect();
        foreach ($skillsData as $skillString) {
            $skills = array_map('trim', explode(',', $skillString));
            $allSkills = $allSkills->merge($skills);
        }

        $filteredSkills = $allSkills
            ->filter(function($skill) use ($search) {
                return empty($search) || stripos($skill, $search) !== false;
            })
            ->unique()
            ->values()
            ->take(10);

        return response()->json([
            'results' => $filteredSkills->map(function ($skill) {
                return [
                    'id' => $skill,
                    'text' => $skill
                ];
            })
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
        $jobApplications = JobApplication::with(['education', 'experiences', 'languages'])
            ->where('job_posting_id', $jobPosting->id);

        $search = $request->query('search');

        // Apply search filter
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

        // Apply education filters
        if ($request->filled('education_institution')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('institution', $request->education_institution);
            });
        }

        if ($request->filled('education_degree')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('degree', $request->education_degree);
            });
        }

        if ($request->filled('education_field')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('field_of_study', $request->education_field);
            });
        }

        if ($request->filled('education_start_year')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('start_year', '>=', $request->education_start_year);
            });
        }

        if ($request->filled('education_end_year')) {
            $jobApplications->whereHas('education', function($query) use ($request) {
                $query->where('end_year', '<=', $request->education_end_year);
            });
        }

        // Apply work experience filters
        if ($request->filled('work_company')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('company_name', $request->work_company);
            });
        }

        if ($request->filled('work_title')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('job_title', $request->work_title);
            });
        }

        if ($request->filled('work_start_year')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('start_year', '>=', $request->work_start_year);
            });
        }

        if ($request->filled('work_end_year')) {
            $jobApplications->whereHas('experiences', function($query) use ($request) {
                $query->where('end_year', '<=', $request->work_end_year);
            });
        }

        // Apply language filters
        if ($request->filled('language_name')) {
            $jobApplications->whereHas('languages', function($query) use ($request) {
                $query->where('name', $request->language_name);
            });
        }

        if ($request->filled('language_proficiency')) {
            $jobApplications->whereHas('languages', function($query) use ($request) {
                $query->where('proficiency', $request->language_proficiency);
            });
        }

        // Apply nationality filter
        if ($request->filled('nationality')) {
            $jobApplications->where('nationality', $request->nationality);
        }

        // Apply skills filter
        if ($request->filled('skills')) {
            $jobApplications->where('skills', 'like', '%' . $request->skills . '%');
        }

        // Apply AI score filter
        if ($request->filled('ai_score_min')) {
            $jobApplications->where('ai_score', '>=', $request->ai_score_min);
        }

        $jobApplications = $jobApplications->get();

        // Collect applied filters for CSV metadata
        $appliedFilters = [];
        if ($search) $appliedFilters['Search'] = $search;
        if ($request->filled('education_institution')) $appliedFilters['Education Institution'] = $request->education_institution;
        if ($request->filled('education_degree')) $appliedFilters['Education Degree'] = $request->education_degree;
        if ($request->filled('education_field')) $appliedFilters['Education Field'] = $request->education_field;
        if ($request->filled('education_start_year')) $appliedFilters['Education Start Year'] = $request->education_start_year;
        if ($request->filled('education_end_year')) $appliedFilters['Education End Year'] = $request->education_end_year;
        if ($request->filled('work_company')) $appliedFilters['Work Company'] = $request->work_company;
        if ($request->filled('work_title')) $appliedFilters['Work Title'] = $request->work_title;
        if ($request->filled('work_start_year')) $appliedFilters['Work Start Year'] = $request->work_start_year;
        if ($request->filled('work_end_year')) $appliedFilters['Work End Year'] = $request->work_end_year;
        if ($request->filled('language_name')) $appliedFilters['Language'] = $request->language_name;
        if ($request->filled('language_proficiency')) $appliedFilters['Language Proficiency'] = $request->language_proficiency;
        if ($request->filled('nationality')) $appliedFilters['Nationality'] = $request->nationality;
        if ($request->filled('skills')) $appliedFilters['Skills'] = $request->skills;
        if ($request->filled('ai_score_min')) $appliedFilters['AI Score Min'] = $request->ai_score_min;

        // Generate filename with filter indicator
        $filename = 'job_applications_' . date('Y-m-d');
        if (!empty($appliedFilters)) {
            $filename .= '_filtered';
        }
        $filename .= '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($jobApplications, $appliedFilters, $jobPosting) {
            $file = fopen('php://output', 'w');
            
            // Add metadata at the top of CSV
            fputcsv($file, ['Job Posting Export Report']);
            fputcsv($file, ['Job Posting', $jobPosting->name]);
            fputcsv($file, ['Export Date', date('Y-m-d H:i:s')]);
            fputcsv($file, ['Total Applications', count($jobApplications)]);
            fputcsv($file, []); // Empty row
            
            // Add applied filters if any
            if (!empty($appliedFilters)) {
                fputcsv($file, ['Applied Filters:']);
                foreach ($appliedFilters as $filterName => $filterValue) {
                    fputcsv($file, ['', $filterName, $filterValue]);
                }
                fputcsv($file, []); // Empty row
            }
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Nationality', 'Address',
                'Skills', 'AI Score', 'AI Scored Date', 'Applied Date',
                'Education Institutions', 'Education Degrees', 'Education Fields', 'Education Years',
                'Work Companies', 'Work Titles', 'Work Years', 'Current Position',
                'Languages', 'Language Proficiencies'
            ]);

            foreach ($jobApplications as $app) {
                // Format education data
                $institutions = $app->education->pluck('institution')->implode('; ');
                $degrees = $app->education->pluck('degree')->implode('; ');
                $fields = $app->education->pluck('field_of_study')->implode('; ');
                $eduYears = $app->education->map(function($edu) {
                    return $edu->start_year . '-' . $edu->end_year;
                })->implode('; ');

                // Format work experience data
                $companies = $app->experiences->pluck('company_name')->implode('; ');
                $jobTitles = $app->experiences->pluck('job_title')->implode('; ');
                $workYears = $app->experiences->map(function($exp) {
                    return $exp->start_year . '-' . ($exp->end_year ?: 'Present');
                })->implode('; ');
                $currentPosition = $app->experiences->where('is_current', true)->first()?->job_title ?? 'N/A';

                // Format language data
                $languages = $app->languages->pluck('name')->implode('; ');
                $proficiencies = $app->languages->map(function($lang) {
                    return $lang->name . ' (' . ucfirst($lang->proficiency) . ')';
                })->implode('; ');

                fputcsv($file, [
                    $app->id,
                    $app->first_name,
                    $app->last_name,
                    $app->email,
                    $app->phone,
                    $app->nationality,
                    $app->address,
                    $app->skills,
                    $app->ai_score ?? 'Pending',
                    $app->ai_scored_at ? $app->ai_scored_at->format('Y-m-d H:i:s') : 'Not scored',
                    $app->created_at->format('Y-m-d H:i:s'),
                    $institutions,
                    $degrees,
                    $fields,
                    $eduYears,
                    $companies,
                    $jobTitles,
                    $workYears,
                    $currentPosition,
                    $languages,
                    $proficiencies
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
