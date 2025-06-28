<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Pages\DeletePageRequest;
use App\Http\Requests\Admin\Pages\StorePageRequest;
use App\Http\Requests\Admin\Pages\UpdatePageRequest;
use App\Http\Requests\Admin\Pages\UpdatePageTranslationRequest;
use App\Models\Language;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PagesController extends Controller
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

        $pages = Page::latest();

        if ($search) {
            $pages->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $pages = $pages->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'pages' => $pages->items(),
                'pagination' => $this->indexService->handlePagination($pages)
            ]);
        }

        return inertia('Pages/Index');
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
        $menus = Menu::get();

        return view('admin.pages.create', compact('defaultLanguage', 'medias', 'menus'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePageRequest $request)
    {
        $page = Page::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();


        foreach($page->getTranslatableFields() as $field){
            $page->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Page', $page->id, true);
    
        return redirect()->route('admin.pages.index')
                        ->with('success','Page created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {    
        $page->load('menu');

        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $page->getTranslatableFieldsByLanguages();

        return Inertia::render('Pages/Show', [
            'page' => $page,
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
    public function edit(Page $page)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $medias = Media::where('type', 'image')->get();
        $menus = Menu::get();

        return view('admin.pages.edit', compact('page', 'languages', 'medias', 'menus'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Page $page, UpdatePageRequest $request)
    {
        $page->update(array_merge(
            $request->validated(),
            [
                'slug' => ($page->is_system_page) ? $page->slug : Str::slug($request->slug)
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
            $page->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Page', $page->id, true);
        }
    
        cache()->forget("page-{$page->id}");

        return redirect()->route('admin.pages.index')
                        ->with('success','Page updated successfully');
    }

    public function updateTranslation(Page $page, UpdatePageTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($page->getTranslatableFields() as $field){
            $page->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Page updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page, DeletePageRequest $request)
    {
        dd($page);
        //$page->delete();

        //cache()->forget("page-{$page->id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Page deleted successfully',
        ]);
    }
}
