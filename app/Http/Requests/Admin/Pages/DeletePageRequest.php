<?php

namespace App\Http\Requests\Admin\Pages;

use Illuminate\Foundation\Http\FormRequest;

class DeletePageRequest extends FormRequest
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
        $page = $this->route('page');

        $validator->after(function ($validator) use ($page){
            if ($page->is_system_page) {
                $validator->errors()->add('page', 'This page cannot be deleted as it is a system page.');

                return;
            }
        });
    }
}
