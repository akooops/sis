<?php

namespace App\Http\Requests\Admin\Forms;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateFormRequest extends FormRequest
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
        $form = $this->route('form');

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:forms,slug',
            'status' => 'required|string|in:draft,hidden,published',
            'has_captcha' => 'required|boolean',
        ];
    }
}
