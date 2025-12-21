<?php

namespace App\Http\Requests\Admin\AchievementCategories;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievement_categories,slug',
            'title' => 'required|string|max:1000',
        ];
    }
}
