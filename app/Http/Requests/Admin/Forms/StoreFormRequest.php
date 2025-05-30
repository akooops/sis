<?php

namespace App\Http\Requests\Admin\Forms;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:forms,slug',
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:3000',
            'status' => 'required|string|in:draft,hidden,published',
            'has_captcha' => 'required|boolean',
        ];
    }
}
