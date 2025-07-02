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

        $jobPostings = $jobPostings->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'jobPostings' => $jobPostings->items(),
                'pagination' => $this->indexService->handlePagination($jobPostings)
            ]);
        }

        return inertia('JobPostings/Index');
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

        return inertia('JobPostings/Create', compact('defaultLanguage'));
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

        $media = null;

        if ($request->hasFile('file')) {
            // Get MIME type
            $mimeType = $request->file('file')->getMimeType();
            
            // Determine file category using match expression
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default => 'document',
            };

            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => $type
                ]
            ));

            $defaultLanguage = Language::where([
                'is_default' => true,
            ])->first();

            foreach($media->getTranslatableFields() as $field){
                $media->setTranslation($field, $defaultLanguage->code, $request->input($field));    
            }
            
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\JobPosting', $jobPosting->id, true);

        return inertia('JobPostings/Index', [
            'success' => 'Job Posting created successfully!'
        ]);
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
        $translations = $jobPosting->getTranslatableFieldsByLanguages();

        return inertia('JobPostings/Show', [
            'jobPosting' => $jobPosting,
            'languages' => $languages,
            'translations' => $translations
        ]);
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
        $translations = $jobPosting->getTranslatableFieldsByLanguages();

        return inertia('JobPostings/Edit', [
            'jobPosting' => $jobPosting,
            'languages' => $languages,
            'translations' => $translations
        ]);
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

        $media = null;

        if ($request->hasFile('file')) {
            // Get MIME type
            $mimeType = $request->file('file')->getMimeType();
            
            // Determine file category using match expression
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default => 'document',
            };

            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => $type
                ]
            ));
            
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }
    
        if($media){
            if($jobPosting->file) $jobPosting->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\JobPosting', $jobPosting->id, true);
        }
    
        return inertia('JobPostings/Index', [
            'success' => 'Job Posting updated successfully!'
        ]);
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
