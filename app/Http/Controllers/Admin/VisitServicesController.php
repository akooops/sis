<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VisitServices\OrderVisitServicesRequest;
use App\Http\Requests\Admin\VisitServices\StoreVisitServiceRequest;
use App\Http\Requests\Admin\VisitServices\UpdateVisitServiceRequest;
use App\Http\Requests\Admin\VisitServices\UpdateVisitServiceTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\VisitService;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class VisitServicesController extends Controller
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

        $visitServices = VisitService::latest();

        if ($search) {
            $visitServices->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $visitServices = $visitServices->paginate($perPage, ['*'], 'visitService', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'visitServices' => $visitServices->items(),
                'pagination' => $this->indexService->handlePagination($visitServices)
            ]);
        }

        return inertia('VisitServices/Index');    
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

        return inertia('VisitServices/Create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVisitServiceRequest $request)
    {
        $visitService = VisitService::create($request->validated());
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($visitService->getTranslatableFields() as $field){
            $visitService->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\VisitService', $visitService->id, true);

        return inertia('VisitServices/Index', [
            'success' => 'Visit Service created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VisitService $visitService)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $visitService->getTranslatableFieldsByLanguages();

        return inertia('VisitServices/Show', [
            'visitService' => $visitService,
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
    public function edit(VisitService $visitService)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $visitService->getTranslatableFieldsByLanguages();

        return inertia('VisitServices/Edit', [
            'visitService' => $visitService,
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
    public function update(VisitService $visitService, UpdateVisitServiceRequest $request)
    {
        $visitService->update($request->validated());

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
            if($visitService->file) $visitService->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\VisitService', $visitService->id, true);
        }

        return inertia('VisitServices/Index', [
            'success' => 'Visit Service updated successfully!'
        ]);
    }

    public function updateTranslation(VisitService $visitService, UpdateVisitServiceTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($visitService->getTranslatableFields() as $field){
            $visitService->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Visit Service updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitService $visitService)
    {
        $visitService->delete();

        return redirect()->route('admin.visit-services.index')
                        ->with('success','Visit Service deleted successfully');
    }

    public function orderPage()
    {
        $visitServices = VisitService::orderBy('order')->get();

        return inertia('VisitServices/Order', compact('visitServices'));
    }

    public function order(OrderVisitServicesRequest $request)
    {
        foreach ($request->order as $item) {
            VisitService::where('id', $item['id'])
                ->update([
                    'order' => $item['order']
            ]);
        }
            
        return response()->json([
            'status' => 'success',
            'message' => 'Visit Services ordered successfully',    
        ]);
    }
}
