<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Nationalities\StoreNationalityRequest;
use App\Http\Requests\Admin\Nationalities\UpdateNationalityRequest;
use App\Http\Requests\Admin\Nationalities\UpdateNationalityTranslationRequest;
use App\Models\Language;
use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NationalitiesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $nationalities = Nationality::query()->orderBy('code');

        if ($search) {
            $nationalities->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $nationalities = $nationalities->paginate($perPage, ['*'], 'nationality', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'nationalities' => $nationalities->items(),
                'pagination' => $this->indexService->handlePagination($nationalities),
            ]);
        }

        return inertia('Nationalities/Index');
    }

    public function create()
    {
        $defaultLanguage = Language::where('is_default', true)->first();

        return inertia('Nationalities/Create', compact('defaultLanguage'));
    }

    public function store(StoreNationalityRequest $request)
    {
        $validated = $request->validated();

        $defaultLanguage = Language::where('is_default', true)->first();

        $nationality = Nationality::create([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
        ]);

        if ($defaultLanguage) {
            $nationality->setTranslation('title', $defaultLanguage->code, $validated['title']);
        }

        return inertia('Nationalities/Index', [
            'success' => 'Nationality created successfully!',
        ]);
    }

    public function show(Nationality $nationality)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $nationality->getTranslatableFieldsByLanguages();

        return inertia('Nationalities/Show', [
            'nationality' => $nationality,
            'languages' => $languages,
            'translations' => $translations,
        ]);
    }

    public function edit(Nationality $nationality)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $nationality->getTranslatableFieldsByLanguages();

        return inertia('Nationalities/Edit', compact('nationality', 'languages', 'translations'));
    }

    public function update(Nationality $nationality, UpdateNationalityRequest $request)
    {
        $validated = $request->validated();

        $nationality->update([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
        ]);

        return inertia('Nationalities/Index', [
            'success' => 'Nationality updated successfully!',
        ]);
    }

    public function updateTranslation(Nationality $nationality, UpdateNationalityTranslationRequest $request)
    {
        $validated = $request->validated();

        $language = Language::find($validated['language_id']);
        $nationality->setTranslation('title', $language->code, $validated['title']);

        return response()->json([
            'status' => 'success',
            'message' => 'Nationality updated successfully',
        ]);
    }

    public function destroy(Nationality $nationality)
    {
        $nationality->delete();

        return redirect()->route('admin.nationalities.index')
            ->with('success', 'Nationality deleted successfully');
    }
}
