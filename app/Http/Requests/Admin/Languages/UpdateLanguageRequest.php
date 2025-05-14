<?php

namespace App\Http\Requests\Admin\Languages;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
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
        $language = request()->route('language');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|regex:/^[a-z]{2}(-[A-Z]{2})?$/|unique:languages,code,'.$language->id,
            'is_rtl' => 'required|boolean',
            'is_default' => 'required|boolean',
        ];
    }

    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $language = request()->route('language');
            if($language->is_default && $this->has('is_default') && !$this->input('is_default')){
                $validator->errors()->add("is_default", 'The default language status cannot be changed.');
            }
        });
    }
}
