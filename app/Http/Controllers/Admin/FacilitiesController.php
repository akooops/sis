<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Facilities\StoreFacilityRequest;
use App\Http\Requests\Admin\Facilities\UpdateFacilityRequest;
use App\Http\Requests\Admin\Facilities\UpdateFacilityTranslationRequest;
use App\Models\Facility;
use App\Models\Language;
use App\Models\Media;
use Illuminate\Http\Request;

class FacilitiesController extends Controller
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

        $facilities = Facility::orderBy('order');

        if ($search) {
            $facilities->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('slug', 'like', '%' . $search . '%')
                      ->orWhere('domain', 'like', '%' . $search . '%');
            });
        }

        $facilities = $facilities->paginate($perPage, ['*'], 'facility', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'facilities' => $facilities->items(),
                'pagination' => $this->indexService->handlePagination($facilities)
            ]);
        }

        return inertia('Facilities/Index');
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

        return inertia('Facilities/Create', compact('defaultLanguage'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFacilityRequest $request)
    {
        $facility = Facility::create($request->validated());

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($facility->getTranslatableFields() as $field){
            $facility->setTranslation($field, $defaultLanguage->code, $request->input($field));
        }

        $media = null;

        if ($request->hasFile('file')) {
            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => 'image'
                ]
            ));

            foreach($media->getTranslatableFields() as $field){
                $media->setTranslation($field, $defaultLanguage->code, $request->input($field));
            }

            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }

        if ($media) {
            $this->fileService->duplicateMediaFile($media, 'App\\Models\\Facility', $facility->id, true);
        }

        if ($request->hasFile('logo')) {
            $logoFile = $this->fileService->upload($request->file('logo'));
            $facility->update(['logo_file_id' => $logoFile->id]);
        }

        return inertia('Facilities/Index', [
            'success' => 'Facility created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Facility $facility)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $facility->getTranslatableFieldsByLanguages();

        $facility->load('logoFile');

        return inertia('Facilities/Show', [
            'facility' => $facility,
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
    public function edit(Facility $facility)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $facility->getTranslatableFieldsByLanguages();

        $facility->load('logoFile');

        return inertia('Facilities/Edit', [
            'facility' => $facility,
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
    public function update(Facility $facility, UpdateFacilityRequest $request)
    {
        $facility->update($request->validated());

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
            if($facility->file) $facility->file->detach();
            $this->fileService->duplicateMediaFile($media, 'App\\Models\\Facility', $facility->id, true);
        }

        if ($request->hasFile('logo')) {
            $logoFile = $this->fileService->upload($request->file('logo'));
            $facility->update(['logo_file_id' => $logoFile->id]);
        }

        return inertia('Facilities/Index', [
            'success' => 'Facility updated successfully!'
        ]);
    }

    public function updateTranslation(Facility $facility, UpdateFacilityTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($facility->getTranslatableFields() as $field){
            $facility->setTranslation($field, $language->code, $request->input($field));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Facility updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Facility $facility)
    {
        $facility->delete();

        return redirect()->route('admin.facilities.index')
                        ->with('success','Facility deleted successfully');
    }
}
