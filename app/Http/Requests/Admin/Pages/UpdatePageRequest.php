<?php

namespace App\Http\Requests\Admin\Pages;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
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
        $page = request()->route('page');

        $data = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:pages,slug,'.$page->id,
            'status' => 'required|string|in:draft,hidden,published',
        ];

        if($page->is_system_page){
            unset($data['name']);
            unset($data['slug']);
        }

        return $data;
    }
}
