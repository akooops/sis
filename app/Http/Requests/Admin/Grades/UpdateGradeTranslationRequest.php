<?php

namespace App\Http\Requests\Admin\Grades;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $data = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'required|string',
            'language_id' => 'required|exists:languages,id',
        ];
        
        return $data;
    }
}
