<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaTypes;
use App\Http\Requests\Admin\Media\DeleteMediaRequest;
use App\Http\Requests\Admin\Media\StoreMediaRequest;
use App\Http\Requests\Admin\Media\UpdateMediaRequest;
use App\Http\Requests\Admin\Media\UpdateMediaTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Route;

class MediaController extends Controller
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

        $type = $this->indexService->checkIfEnumHasValue($request->query('type'), MediaTypes::class);

        $media = Media::latest();

        if($type){
            $media->where('type', $type);
        }

        if ($search) {
            $media->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $media = $media->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'medias' => $media->items(), 
                'pagination' => $this->indexService->handlePagination($media)
            ]);
        }

        return inertia('Media/Index', [
            'medias' => $media->items(), 
            'pagination' => $this->indexService->handlePagination($media)
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

        return view('admin.media.create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMediaRequest $request)
    {
        $validatedData = $request->validated();
        
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
        
        if($request->has('file')){
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        }

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($media->getTranslatableFields() as $field){
            $media->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }
    
        return redirect()->route('admin.media.index')
                        ->with('success','Media created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Media $media)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.media.show', compact('media', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Media $media)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.media.edit', compact('media', 'languages'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Media $media, UpdateMediaRequest $request)
    {
        $media->update($request->validated());
    
        return redirect()->route('admin.media.index')
                        ->with('success','Media updated successfully');
    }

    public function updateTranslation(Media $media, UpdateMediaTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($media->getTranslatableFields() as $field){
            $media->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Media updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Media $media, DeleteMediaRequest $request)
    {
        $media->delete();
        
        return redirect()->route('admin.media.index')
                        ->with('success','Media deleted successfully');
    }
}
