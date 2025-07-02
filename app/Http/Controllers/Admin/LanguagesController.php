<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Languages\DeleteLanguageRequest;
use App\Http\Requests\Admin\Languages\StoreLanguageRequest;
use App\Http\Requests\Admin\Languages\UpdateLanguageRequest;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

class LanguagesController extends Controller
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

        $languages = Language::orderBy('is_default', 'DESC');

        if ($search) {
            $languages->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $languages = $languages->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'languages' => $languages->items(),
                'pagination' => $this->indexService->handlePagination($languages)
            ]);
        }

        return inertia('Languages/Index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Languages/Create');
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLanguageRequest $request)
    {
        if($request->input('is_default') == true){
            $defaultLanguage = Language::where([
                'is_default' => true,
            ])->update([
                'is_default' => false
            ]);
        }

        $language = Language::create($request->validated());
    
        cache()->forget("all-languages");

        return inertia('Languages/Index', [
            'success' => 'Language created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Language $language)
    {    
        return inertia('Languages/Show', compact('language'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Language $language)
    {
        return inertia('Languages/Edit', compact('language'));
    }   
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Language $language, UpdateLanguageRequest $request)
    {
        if($request->input('is_default') == true && $language->is_default == false){
            $defaultLanguage = Language::where([
                'is_default' => true,
            ])->update([
                'is_default' => false
            ]);
        }

        $language->update($request->validated());
        
        cache()->forget("all-languages");

        return inertia('Languages/Index', [
            'success' => 'Language updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Language $language, DeleteLanguageRequest $request)
    {
        $language->delete();

        cache()->forget("all-languages");

        return redirect()->route('admin.languages.index')
                        ->with('success','Language deleted successfully');
    }
}
