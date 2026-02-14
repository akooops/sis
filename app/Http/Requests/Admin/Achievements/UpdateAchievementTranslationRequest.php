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
            'done_by' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'required|string',
            'language_id' => 'required|exists:languages,id',
        ];
    }
}
