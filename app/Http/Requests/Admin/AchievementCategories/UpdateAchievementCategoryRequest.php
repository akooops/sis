<?php

namespace App\Http\Requests\Admin\AchievementCategories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('achievement_category');
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievement_categories,slug,' . $category->id,
        ];
    }
}
