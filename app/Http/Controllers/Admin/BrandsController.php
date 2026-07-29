<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Brands\StoreBrandRequest;
use App\Http\Requests\Admin\Brands\UpdateBrandRequest;
use App\Http\Requests\Admin\Brands\UpdateBrandTranslationRequest;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\File;
use App\Models\Language;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandsController extends Controller
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

        $brands = Brand::withCount('assets')->orderBy('order');

        if ($search) {
            $brands->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $brands = $brands->paginate($perPage, ['*'], 'brand', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'brands' => $brands->items(),
                'pagination' => $this->indexService->handlePagination($brands)
            ]);
        }

        return inertia('Brands/Index');
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

        return inertia('Brands/Create', compact('defaultLanguage'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBrandRequest $request)
    {
        $brand = Brand::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug ?: $request->name),
            ]
        ));

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($brand->getTranslatableFields() as $field){
            $brand->setTranslation($field, $defaultLanguage->code, $request->input($field));
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
            $this->fileService->duplicateMediaFile($media, 'App\\Models\\Brand', $brand->id, true);
        }

        $this->syncAssets($brand, $request->input('assets', []));

        return inertia('Brands/Index', [
            'success' => 'Brand created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Brand $brand)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $brand->getTranslatableFieldsByLanguages();

        $brand->load('assets.file');

        return inertia('Brands/Show', [
            'brand' => $brand,
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
    public function edit(Brand $brand)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $brand->getTranslatableFieldsByLanguages();

        $brand->load('assets.file');

        return inertia('Brands/Edit', [
            'brand' => $brand,
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
    public function update(Brand $brand, UpdateBrandRequest $request)
    {
        $brand->update(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug ?: $brand->slug),
            ]
        ));

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
            if($brand->file) $brand->file->detach();
            $this->fileService->duplicateMediaFile($media, 'App\\Models\\Brand', $brand->id, true);
        }

        $this->syncAssets($brand, $request->input('assets', []));

        return inertia('Brands/Index', [
            'success' => 'Brand updated successfully!'
        ]);
    }

    public function updateTranslation(Brand $brand, UpdateBrandTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($brand->getTranslatableFields() as $field){
            $brand->setTranslation($field, $language->code, $request->input($field));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Brand updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        // Delete assets one by one so each asset's file is cleaned up too.
        foreach ($brand->assets as $asset) {
            $asset->delete();
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')
                        ->with('success','Brand deleted successfully');
    }

    /**
     * Sync the brand's asset list: create new assets from uploaded file ids,
     * update kept ones, delete removed ones (along with their files).
     */
    protected function syncAssets(Brand $brand, array $assets)
    {
        $keptIds = [];

        foreach ($assets as $index => $data) {
            if (!empty($data['id'])) {
                $asset = $brand->assets()->find($data['id']);

                if ($asset) {
                    $asset->update([
                        'name' => $data['name'],
                        'group' => $data['group'],
                        'order' => $data['order'] ?? $index,
                    ]);

                    $keptIds[] = $asset->id;
                }

                continue;
            }

            if (empty($data['file_id'])) {
                continue;
            }

            $file = File::find($data['file_id']);

            if (! $file || $file->model_id) {
                continue;
            }

            $asset = BrandAsset::create([
                'brand_id' => $brand->id,
                'name' => $data['name'] ?: ($file->original_name ?? $file->name),
                'group' => $data['group'],
                'order' => $data['order'] ?? $index,
            ]);

            $file->update([
                'model_type' => 'App\\Models\\BrandAsset',
                'model_id' => $asset->id,
                'is_main' => 1,
            ]);

            $keptIds[] = $asset->id;
        }

        // Delete assets that were removed in the form (deleting cleans up files)
        $brand->assets()->whereNotIn('id', $keptIds)->get()->each->delete();
    }
}
