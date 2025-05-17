<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Albums\DeleteAlbumRequest;
use App\Http\Requests\Admin\Albums\StoreAlbumRequest;
use App\Http\Requests\Admin\Albums\UpdateAlbumRequest;
use App\Http\Requests\Admin\Albums\UpdateAlbumTranslationRequest;
use App\Models\File;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Media;
use App\Models\Program;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AlbumsController extends Controller
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

        $albums = Album::latest();

        if($program_id){
            $albums->where('program_id', $program_id);
        }

        if ($search) {
            $albums->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $albums = $albums->paginate($perPage, ['*'], 'album', $page);

        return view('admin.albums.index', [
            'albums' => $albums,
            'pagination' => $this->indexService->handlePagination($albums)
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

        return view('admin.albums.create', compact('defaultLanguage', 'medias'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAlbumRequest $request)
    {
        $album = Album::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($album->getTranslatableFields() as $field){
            $album->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Album', $album->id, true);

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);

                    $type = match (true) {
                        str_starts_with($file->type, 'image/') => 'image',
                        str_starts_with($file->type, 'video/') => 'video',
                        str_starts_with($file->type, 'audio/') => 'audio',
                        default => 'document',
                    };

                    if($type == 'image') $file->attach($album);
                }
            }
        }

        return redirect()->route('admin.albums.index')
                        ->with('success','Album created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Album $album)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.albums.show', compact('album', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Album $album)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $medias = Media::where('type', 'image')->get();

        return view('admin.albums.edit', compact('album', 'languages', 'medias'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Album $album, UpdateAlbumRequest $request)
    {
        $album->update(array_merge(
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
            $album->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Album', $album->id, true);
        }

        $album->files()->where('is_main', false)->update([
            'model_type' => null,
            'model_id' => null
        ]);

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);
                    $file->attach($album);

                    $type = match (true) {
                        str_starts_with($file->type, 'image/') => 'image',
                        str_starts_with($file->type, 'video/') => 'video',
                        str_starts_with($file->type, 'audio/') => 'audio',
                        default => 'document',
                    };

                    if($type == 'image') $file->attach($album);
                }
            }
        }

        return redirect()->route('admin.albums.index')
                        ->with('success','Album updated successfully');
    }

    public function updateTranslation(Album $album, UpdateAlbumTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($album->getTranslatableFields() as $field){
            $album->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Album updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Album $album)
    {
        $album->delete();

        return redirect()->route('admin.albums.index')
                        ->with('success','Album deleted successfully');
    }
}
