<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\JobPostings\DeleteJobPostingRequest;
use App\Http\Requests\Admin\JobPostings\StoreJobPostingRequest;
use App\Http\Requests\Admin\JobPostings\UpdateJobPostingRequest;
use App\Http\Requests\Admin\JobPostings\UpdateJobPostingTranslationRequest;
use App\Models\File;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\JobPosting;
use App\Models\Media;
use App\Models\Program;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class JobPostingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $jobPostings = JobPosting::latest();

        if ($search) {
            $jobPostings->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $jobPostings = $jobPostings->paginate($perPage, ['*'], 'jobPosting', $page);

        return view('admin.job-postings.index', [
            'jobPostings' => $jobPostings,
            'pagination' => $this->indexService->handlePagination($jobPostings)
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return view('admin.job-postings.create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreJobPostingRequest $request)
    {
        $jobPosting = JobPosting::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($jobPosting->getTranslatableFields() as $field){
            $jobPosting->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }

        return redirect()->route('admin.job-postings.index')
                        ->with('success','Job Posting created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(JobPosting $jobPosting)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.job-postings.show', compact('jobPosting', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(JobPosting $jobPosting)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.job-postings.edit', compact('jobPosting', 'languages'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(JobPosting $jobPosting, UpdateJobPostingRequest $request)
    {
        $jobPosting->update(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
    
        return redirect()->route('admin.job-postings.index')
                        ->with('success','Job Posting updated successfully');
    }

    public function updateTranslation(JobPosting $jobPosting, UpdateJobPostingTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($jobPosting->getTranslatableFields() as $field){
            $jobPosting->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Job Posting updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(JobPosting $jobPosting)
    {
        $jobPosting->delete();

        return redirect()->route('admin.job-postings.index')
                        ->with('success','Job Posting deleted successfully');
    }
}
