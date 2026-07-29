<?php

namespace App\Http\Requests\Admin\Facilities;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilityTranslationRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:1000',
            'description' => 'required|string',
            'content' => 'required|string',
            'address' => 'nullable|string|max:1000',

            'language_id' => 'required|exists:languages,id',
        ];
    }
}
