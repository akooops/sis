<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Programs\DeleteProgramRequest;
use App\Http\Requests\Admin\Programs\StoreProgramRequest;
use App\Http\Requests\Admin\Programs\UpdateProgramRequest;
use App\Http\Requests\Admin\Programs\UpdateProgramTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ProgramsController extends Controller
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

        $programs = Program::latest();

        if ($search) {
            $programs->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $programs = $programs->paginate($perPage, ['*'], 'program', $page);

        return view('admin.programs.index', [
            'programs' => $programs,
            'pagination' => $this->indexService->handlePagination($programs)
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

        $medias = Media::where('type', 'image')->get();
        return view('admin.programs.create', compact('defaultLanguage', 'medias'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProgramRequest $request)
    {
        $program = Program::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($program->getTranslatableFields() as $field){
            $program->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Program', $program->id, true);

        cache()->forget("all-programs");

        return redirect()->route('admin.programs.index')
                        ->with('success','Program created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Program $program)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.programs.show', compact('program', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Program $program)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $medias = Media::where('type', 'image')->get();

        return view('admin.programs.edit', compact('program', 'languages', 'medias'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Program $program, UpdateProgramRequest $request)
    {
        $program->update(array_merge(
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
            $program->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Program', $program->id, true);
        }

        cache()->forget("all-programs");

        return redirect()->route('admin.programs.index')
                        ->with('success','Program updated successfully');
    }

    public function updateTranslation(Program $program, UpdateProgramTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($program->getTranslatableFields() as $field){
            $program->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Program updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Program $program)
    {
        $program->delete();

        cache()->forget("all-programs");
        
        return redirect()->route('admin.programs.index')
                        ->with('success','Program deleted successfully');
    }
}
