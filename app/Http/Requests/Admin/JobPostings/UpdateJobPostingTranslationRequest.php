<?php

namespace App\Http\Requests\Admin\JobPostings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingTranslationRequest extends FormRequest
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
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:3000',
            'content' => 'required|string',
            'required_skills' => 'required|string|max:2000',
            'language_id' => 'required|exists:languages,id',
        ];
        
        return $data;
    }
}
