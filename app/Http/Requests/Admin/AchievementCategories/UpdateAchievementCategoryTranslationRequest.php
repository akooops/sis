<?php

namespace App\Http\Requests\Admin\AchievementCategories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementCategoryTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:1000',
            'language_id' => 'required|exists:languages,id',
        ];
    }
}
