<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Category\CategoryData;
use App\Data\Category\StoreCategoryData;
use App\Data\Category\UpdateCategoryData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoriesController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = QueryBuilder::for(Category::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                // How the content forms find the category to preselect.
                AllowedFilter::exact('is_default'),
                $this->searchTranslations(['id', 'name', 'code'], ['title'], Language::enabledCodes()),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            ->defaultSort('name')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CategoryData::collect($categories, PaginatedDataCollection::class), 'Categories retrieved successfully');
    }

    public function show(Category $category): JsonResponse
    {
        return $this->respond(CategoryData::from($category), 'Category retrieved successfully');
    }

    public function store(StoreCategoryData $data): JsonResponse
    {
        $category = Category::create([
            'name' => $data->name,
            'code' => $data->code,
            'title' => [Language::defaultCode() => $data->title],
            'color' => $data->color,
            'is_default' => $data->is_default,
        ]);

        return $this->respond(CategoryData::from($category), 'Category created successfully', 201);
    }

    public function update(UpdateCategoryData $data, Category $category): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $category->update($category->mergeTranslations([
            'name' => $data->name,
            'code' => $data->code,
            'title' => $data->title,
            'color' => $data->color,
            'is_default' => $data->is_default,
        ]));

        return $this->respond(CategoryData::from($category->fresh()), 'Category updated successfully');
    }

    /**
     * Delete moves the content to the default category instead of refusing.
     *
     * The move is not optional — the FK is restrictOnDelete, so skipping it is a
     * raw database error. Hence the transaction.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->is_default) {
            throw ValidationException::withMessages([
                'category' => 'This is the default category — it is what unfiled content falls back to. Make another category the default first.',
            ]);
        }

        $fallback = Category::default();

        if (! $fallback) {
            throw ValidationException::withMessages([
                'category' => 'There is no default category to move the content to. Set one first.',
            ]);
        }

        DB::transaction(function () use ($category, $fallback) {
            $category->articles()->getQuery()->update(['category_id' => $fallback->id]);
            $category->achievements()->getQuery()->update(['category_id' => $fallback->id]);

            $category->delete();
        });

        return $this->respond(null, 'Category deleted successfully');
    }
}
