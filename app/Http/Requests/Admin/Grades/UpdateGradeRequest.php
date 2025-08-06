<?php

namespace App\Http\Requests\Admin\Grades;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'files' => 'nullable|array',
            'files.*' => 'exists:files,id'
        ];
    }
}
