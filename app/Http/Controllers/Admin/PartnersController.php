<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Partners\StorePartnerRequest;
use App\Http\Requests\Admin\Partners\UpdatePartnerRequest;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Models\Partner;

class PartnersController extends Controller
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

        $partners = Partner::latest();

        if ($search) {
            $partners->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $partners = $partners->paginate($perPage, ['*'], 'partner', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'partners' => $partners->items(),
                'pagination' => $this->indexService->handlePagination($partners)
            ]);
        }

        return inertia('Partners/Index');    
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Partners/Create');
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePartnerRequest $request)
    {
        $partner = Partner::create($request->validated());

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
    
        if ($media) {
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Partner', $partner->id, true);
        }

        return inertia('Partners/Index', [
            'success' => 'Partner created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Partner $partner)
    {    
        return inertia('Partners/Show', [
            'partner' => $partner
        ]);
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Partner $partner)
    {
        return inertia('Partners/Edit', compact('partner'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Partner $partner, UpdatePartnerRequest $request)
    {
        $partner->update($request->validated());

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
            if($partner->file) $partner->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Partner', $partner->id, true);
        }

        return inertia('Partners/Index', [
            'success' => 'Partner updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();

        return redirect()->route('admin.partners.index')
                        ->with('success','Partner deleted successfully');
    }
}

