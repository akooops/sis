<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Forms\StoreFormRequest;
use App\Http\Requests\Admin\Forms\UpdateFormRequest;
use App\Http\Requests\Admin\Forms\UpdateFormTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Media;

class FormsController extends Controller
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

        $forms = Form::latest();

        if ($search) {
            $forms->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $forms = $forms->paginate($perPage, ['*'], 'form', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'forms' => $forms->items(),
                'pagination' => $this->indexService->handlePagination($forms)
            ]);
        }

        return inertia('Forms/Index');
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

        return inertia('Forms/Create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFormRequest $request)
    {
        $form = Form::create($request->validated());
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($form->getTranslatableFields() as $field){
            $form->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
        }
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Form', $form->id, true);

        return inertia('Forms/Index', [
            'success' => 'Form created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Form $form)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $form->getTranslatableFieldsByLanguages();

        return inertia('Forms/Show', [
            'form' => $form,
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
    public function edit(Form $form)    
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $form->getTranslatableFieldsByLanguages();

        return inertia('Forms/Edit', [
            'formItem' => $form,
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
    public function update(Form $form, UpdateFormRequest $request)
    {
        $form->update($request->validated());
    
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
        }
    
        if($media){
            if($form->file) $form->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Form', $form->id, true);
        }

        return inertia('Forms/Index', [
            'success' => 'Form updated successfully!'
        ]);
    }

    public function updateTranslation(Form $form, UpdateFormTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($form->getTranslatableFields() as $field){
            $form->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Form updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('admin.forms.index')
                        ->with('success','Form deleted successfully');
    }
}
