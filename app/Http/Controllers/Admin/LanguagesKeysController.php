<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\LanguageKeys\UpdateLanguageKeyTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\LanguageKey;

class LanguagesKeysController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // If it's an AJAX request, return JSON data
        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
            $page = $this->indexService->checkPageIfNull($request->query('page', 1));
            $search = $this->indexService->checkIfSearchEmpty($request->query('search'));
            $languageId = $request->query('language_id');

            $languageKeys = LanguageKey::orderBy('key');

            if ($search) {
                $languageKeys->where(function($query) use ($search) {
                    $query->where('id', $search)
                          ->orWhere('key', 'like', '%' . $search . '%');
                });
            }

            $languageKeys = $languageKeys->paginate($perPage, ['*'], 'page', $page);

            // If language is selected, load translations for that language
            if ($languageId) {
                $language = Language::find($languageId);
                if ($language) {
                    $languageKeys->getCollection()->transform(function ($languageKey) use ($language) {
                        $languageKey->content = $languageKey->getTranslation('content', $language->code);
                        return $languageKey;
                    });
                }
            }

            return response()->json([
                'languageKeys' => $languageKeys->items(),
                'pagination' => $this->indexService->handlePagination($languageKeys)
            ]);
        }

        // Return the Inertia view for the Svelte component
        return inertia('LanguagesKeys/Index');
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
}
