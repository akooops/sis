<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Banners\DeleteBannerRequest;
use App\Http\Requests\Admin\Banners\OrderBannersRequest;
use App\Http\Requests\Admin\Banners\StoreBannerRequest;
use App\Http\Requests\Admin\Banners\UpdateBannerRequest;
use App\Http\Requests\Admin\Banners\UpdateBannerTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BannersController extends Controller
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

        $banners = Banner::latest();

        if ($search) {
            $banners->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $banners = $banners->paginate($perPage, ['*'], 'banner', $page);

        return view('admin.banners.index', [
            'banners' => $banners,
            'pagination' => $this->indexService->handlePagination($banners)
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
        $pages = Page::get();

        return view('admin.banners.create', compact('defaultLanguage', 'medias', 'pages'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBannerRequest $request)
    {
        $validatedData = $request->validated();

        if($request->input('external')){
            $validatedData['page_id'] = null;
        }else{
            $validatedData['url'] = null;
        }

        $banner = Banner::create($validatedData);
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($banner->getTranslatableFields() as $field){
            $banner->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }

        $media = null;

        if ($request->hasFile('file')) {
            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => 'image'
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Banner', $banner->id, true);

        if ($request->hasFile('video')) {
            $video = $this->fileService->upload($request->file('video'), 'App\\Models\\Banner', $banner->id, false);
        }

        return redirect()->route('admin.banners.index')
                        ->with('success','Banner created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.banners.show', compact('banner', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $medias = Media::where('type', 'image')->get();
        $pages = Page::get();

        return view('admin.banners.edit', compact('banner', 'languages', 'medias', 'pages'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Banner $banner, UpdateBannerRequest $request)
    {
        $validatedData = $request->validated();

        if($request->input('external')){
            $validatedData['page_id'] = null;
        }else{
            $validatedData['url'] = null;
        }

        $banner->update($validatedData);

        $media = null;

        if ($request->hasFile('file')) {
            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => 'image'
                ]
            ));
            
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }
    
        if($media){
            $banner->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Banner', $banner->id, true);
        }

        if ($request->hasFile('video')) {
            if($banner->video) $banner->video->detach();

            $video = $this->fileService->upload($request->file('video'), 'App\\Models\\Banner', $banner->id, false);
        }

        return redirect()->route('admin.banners.index')
                        ->with('success','Banner updated successfully');
    }

    public function updateTranslation(Banner $banner, UpdateBannerTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($banner->getTranslatableFields() as $field){
            $banner->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Banner updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')
                        ->with('success','Banner deleted successfully');
    }

    public function orderPage()
    {
        $banners = Banner::orderBy('order')->get();

        return view('admin.banners.order', compact('banners'));
    }

    public function order(OrderBannersRequest $request)
    {
        foreach ($request->order as $item) {
            Banner::where('id', $item['id'])
                ->update([
                    'order' => $item['order']
            ]);
        }
            
        return response()->json([
            'status' => 'success',
        ]);
    }
}
