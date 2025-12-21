<?php

namespace App\Http\Requests\Admin\Achievements;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'done_by' => 'required|string|max:255',
            'language_id' => 'required|exists:languages,id',
        ];
    }
}
