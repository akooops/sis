<?php

namespace App\Http\Requests\Admin\Languages;

use Illuminate\Foundation\Http\FormRequest;

class DeleteLanguageRequest extends FormRequest
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

        ];
    }

    
    public function withValidator($validator)
    {
        $language = $this->route('language');

        $validator->after(function ($validator) use ($language){
            if ($language->is_default) {
                $validator->errors()->add('language', 'This language cannot be deleted as it is a default language.');
            }
        });
    }
}
