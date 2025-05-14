<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Pages\DeletePageRequest;
use App\Http\Requests\Admin\Pages\StorePageRequest;
use App\Http\Requests\Admin\Pages\UpdatePageRequest;
use App\Http\Requests\Admin\Pages\UpdatePageTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

        return view('admin.pages.index', [
            'pages' => $pages,
            'pagination' => $this->indexService->handlePagination($pages)
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

        return view('admin.pages.create', compact('defaultLanguage'));
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
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.pages.show', compact('page', 'languages'));
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

        return view('admin.pages.edit', compact('page', 'languages'));
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
                'slug' => Str::slug($request->slug)
            ]
        ));
    
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
        $page->delete();

        return redirect()->route('admin.pages.index')
                        ->with('success','Page deleted successfully');
    }
}
