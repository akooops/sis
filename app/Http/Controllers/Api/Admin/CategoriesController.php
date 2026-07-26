<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Category\CategoryData;
use App\Data\Category\StoreCategoryData;
use App\Data\Category\UpdateCategoryData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
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
                // What every content form's picker filters on.
                AllowedFilter::exact('type'),
                $this->search(['id', 'name', 'code']),
            ])
            ->allowedSorts(['id', 'name', 'code', 'type', 'created_at'])
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
            'type' => $data->type,
        ]);

        return $this->respond(CategoryData::from($category), 'Category created successfully', 201);
    }

    public function update(UpdateCategoryData $data, Category $category): JsonResponse
    {
        // Retyping a category in use would strand whatever is filed under it: the
        // record keeps the id, but the picker for its own type no longer lists it.
        if ($data->type !== $category->type->value && $this->usageCount($category) > 0) {
            throw ValidationException::withMessages([
                'type' => 'This category is in use, so its type cannot be changed. Move or delete what is filed under it first.',
            ]);
        }

        $category->update([
            'name' => $data->name,
            'code' => $data->code,
            'type' => $data->type,
        ]);

        return $this->respond(CategoryData::from($category->fresh()), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        // The FK is nullOnDelete, so deleting simply uncategorises — no guard.
        $category->delete();

        return $this->respond(null, 'Category deleted successfully');
    }

    /** How many records are filed under this category, across every type. */
    protected function usageCount(Category $category): int
    {
        return $category->articles()->count() + $category->achievements()->count();
    }
}
