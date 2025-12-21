<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AchievementCategories\StoreAchievementCategoryRequest;
use App\Http\Requests\Admin\AchievementCategories\UpdateAchievementCategoryRequest;
use App\Http\Requests\Admin\AchievementCategories\UpdateAchievementCategoryTranslationRequest;
use App\Models\AchievementCategory;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AchievementCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $categories = AchievementCategory::latest();

        if ($search) {
            $categories->where('name', 'like', '%' . $search . '%');
        }

        $categories = $categories->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'categories' => $categories->items(),
                'pagination' => $this->indexService->handlePagination($categories)
            ]);
        }

        return inertia('AchievementCategories/Index');
    }

    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();
        
        return inertia('AchievementCategories/Create', compact('defaultLanguage'));
    }

    public function store(StoreAchievementCategoryRequest $request)
    {
        $category = AchievementCategory::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));

        $defaultLanguage = Language::where('is_default', true)->first();

        foreach ($category->getTranslatableFields() as $field) {
            $category->setTranslation($field, $defaultLanguage->code, $request->input($field));
        }

        return inertia('AchievementCategories/Index', [
            'success' => 'Category created successfully!'
        ]);
    }

    public function show(AchievementCategory $achievementCategory)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $achievementCategory->getTranslatableFieldsByLanguages();

        return inertia('AchievementCategories/Show', [
            'category' => $achievementCategory,
            'languages' => $languages,
            'translations' => $translations
        ]);
    }

    public function edit(AchievementCategory $achievementCategory)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $achievementCategory->getTranslatableFieldsByLanguages();

        return inertia('AchievementCategories/Edit', compact('achievementCategory', 'languages', 'translations'));
    }

    public function update(AchievementCategory $achievementCategory, UpdateAchievementCategoryRequest $request)
    {
        $achievementCategory->update(array_merge(
            $request->validated(),
            ['slug' => Str::slug($request->slug)]
        ));

        return inertia('AchievementCategories/Index', [
            'success' => 'Category updated successfully!'
        ]);
    }

    public function updateTranslation(AchievementCategory $achievementCategory, UpdateAchievementCategoryTranslationRequest $request)
    {
        $language = Language::find($request->language_id);

        foreach ($achievementCategory->getTranslatableFields() as $field) {
            $achievementCategory->setTranslation($field, $language->code, $request->input($field));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Category translation updated successfully',
        ]);
    }

    public function destroy(AchievementCategory $achievementCategory)
    {
        $achievementCategory->delete();

        return redirect()->route('admin.achievement-categories.index')
            ->with('success', 'Category deleted successfully');
    }
}
