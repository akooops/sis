<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Documents\DeleteDocumentRequest;
use App\Http\Requests\Admin\Documents\StoreDocumentRequest;
use App\Http\Requests\Admin\Documents\UpdateDocumentRequest;
use App\Http\Requests\Admin\Documents\UpdateDocumentTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DocumentsController extends Controller
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

        $documents = Document::latest();

        if ($search) {
            $documents->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $documents = $documents->paginate($perPage, ['*'], 'document', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'documents' => $documents->items(),
                'pagination' => $this->indexService->handlePagination($documents)
            ]);
        }

        return inertia('Documents/Index');
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

        return inertia('Documents/Create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentRequest $request)
    {
        $document = Document::create($request->validated());
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($document->getTranslatableFields() as $field){
            $document->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Document', $document->id, true);

        return inertia('Documents/Index', [
            'success' => 'Document created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Document $document)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $document->getTranslatableFieldsByLanguages();

        return inertia('Documents/Show', [
            'document' => $document,
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
    public function edit(Document $document)    
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $document->getTranslatableFieldsByLanguages();

        return inertia('Documents/Edit', [
            'documentItem' => $document,
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
    public function update(Document $document, UpdateDocumentRequest $request)
    {
        $document->update($request->validated());
    
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
            if($document->file) $document->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Document', $document->id, true);
        }

        return inertia('Documents/Index', [
            'success' => 'Document updated successfully!'
        ]);
    }

    public function updateTranslation(Document $document, UpdateDocumentTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($document->getTranslatableFields() as $field){
            $document->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()->route('admin.documents.index')
                        ->with('success','Document deleted successfully');
    }
}
