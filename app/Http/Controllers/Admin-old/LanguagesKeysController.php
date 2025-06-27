<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\LanguageKeys\StoreLanguageKeyRequest;
use App\Http\Requests\Admin\LanguageKeys\UpdateLanguageKeyRequest;
use App\Http\Requests\Admin\LanguageKeys\UpdateLanguageKeyTranslationRequest;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\LanguageKey;
use Illuminate\Support\Facades\Route;

class LanguagesKeysController extends Controller
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

        $languageKeys = LanguageKey::latest();

        if ($search) {
            $languageKeys->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('key', 'like', '%' . $search . '%');
            });
        }

        $languageKeys = $languageKeys->paginate($perPage, ['*'], 'page', $page);

        return view('admin.language-keys.index', [
            'languageKeys' => $languageKeys,
            'pagination' => $this->indexService->handlePagination($languageKeys)
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

        return view('admin.language-keys.create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLanguageKeyRequest $request)
    {
        $languageKey = LanguageKey::create($request->validated());
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($languageKey->getTranslatableFields() as $field){
            $languageKey->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }
    
        return redirect()->route('admin.language-keys.index')
                        ->with('success','Language Key created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(LanguageKey $languageKey)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.language-keys.show', compact('languageKey', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(LanguageKey $languageKey)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.language-keys.edit', compact('languageKey', 'languages'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LanguageKey $languageKey, UpdateLanguageKeyRequest $request)
    {
        $languageKey->update($request->validated());
    
        return redirect()->route('admin.language-keys.index')
                        ->with('success','Language Key updated successfully');
    }

    public function updateTranslation(LanguageKey $languageKey, UpdateLanguageKeyTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($languageKey->getTranslatableFields() as $field){
            $languageKey->setTranslation($field, $language->code, $request->input($field));    
        }

        cache()->forget("language-key-{$languageKey->key}-{$language->code}");

        return response()->json([
            'status' => 'success',
            'message' => 'Language Key updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(LanguageKey $languageKey)
    {
        $languageKey->delete();

        return redirect()->route('admin.language-keys.index')
                        ->with('success','Language Key deleted successfully');
    }
}
