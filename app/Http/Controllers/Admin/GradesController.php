<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Grades\DeleteGradeRequest;
use App\Http\Requests\Admin\Grades\StoreGradeRequest;
use App\Http\Requests\Admin\Grades\UpdateGradeRequest;
use App\Http\Requests\Admin\Grades\UpdateGradeTranslationRequest;
use App\Models\File;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Grade;
use App\Models\Media;
use App\Models\Program;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GradesController extends Controller
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

        $program_id = $this->indexService->checkIfSearchEmpty($request->query('program_id'));

        $grades = Grade::latest();
        $programs = Program::get();

        if($program_id){
            $grades->where('program_id', $program_id);
        }

        if ($search) {
            $grades->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $grades = $grades->paginate($perPage, ['*'], 'grade', $page);

        return view('admin.grades.index', [
            'grades' => $grades,
            'programs' => $programs,
            'pagination' => $this->indexService->handlePagination($grades)
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

        $programs = Program::get();
        $medias = Media::where('type', 'image')->get();


        return view('admin.grades.create', compact('defaultLanguage', 'medias', 'programs'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGradeRequest $request)
    {
        $grade = Grade::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($grade->getTranslatableFields() as $field){
            $grade->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Grade', $grade->id, true);

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);
                    $file->attach($grade);
                }
            }
        }

        return redirect()->route('admin.grades.index')
                        ->with('success','Grade created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Grade $grade)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.grades.show', compact('grade', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Grade $grade)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $medias = Media::where('type', 'image')->get();
        $programs = Program::get();

        return view('admin.grades.edit', compact('grade', 'languages', 'medias', 'programs'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Grade $grade, UpdateGradeRequest $request)
    {
        $grade->update(array_merge(
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
            $grade->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Grade', $grade->id, true);
        }

        $grade->files()->where('is_main', false)->update([
            'model_type' => null,
            'model_id' => null
        ]);

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);
                    $file->attach($grade);
                }
            }
        }

        return redirect()->route('admin.grades.index')
                        ->with('success','Grade updated successfully');
    }

    public function updateTranslation(Grade $grade, UpdateGradeTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($grade->getTranslatableFields() as $field){
            $grade->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Grade updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Grade $grade)
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')
                        ->with('success','Grade deleted successfully');
    }
}
