<?php

namespace App\Http\Requests\Admin\MenuItems;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateMenuItemRequest extends FormRequest
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
            'external' => 'required|integer',
            'url' => 'nullable|required_without:page_id|url',
            'page_id' => 'nullable|required_without:url|exists:pages,id',
        ];
    }
}
