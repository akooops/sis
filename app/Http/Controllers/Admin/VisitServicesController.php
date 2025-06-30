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

        return view('admin.visit-services.index', [
            'visitServices' => $visitServices,
            'pagination' => $this->indexService->handlePagination($visitServices)
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

        return view('admin.visit-services.create', compact('defaultLanguage', 'medias'));
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

        return redirect()->route('admin.visit-services.index')
                        ->with('success','VisitService created successfully');
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

        return view('admin.visit-services.show', compact('visitService', 'languages'));
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
        $medias = Media::where('type', 'image')->get();

        return view('admin.visit-services.edit', compact('visitService', 'languages', 'medias'));
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

        return redirect()->route('admin.visit-services.index')
                        ->with('success','VisitService updated successfully');
    }

    public function updateTranslation(VisitService $visitService, UpdateVisitServiceTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($visitService->getTranslatableFields() as $field){
            $visitService->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'VisitService updated successfully',
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
                        ->with('success','VisitService deleted successfully');
    }

    public function orderPage()
    {
        $visitServices = VisitService::orderBy('order')->get();

        return view('admin.visit-services.order', compact('visitServices'));
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
        ]);
    }
}
