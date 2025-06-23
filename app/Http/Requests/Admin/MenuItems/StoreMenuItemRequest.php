<?php

namespace App\Http\Requests\Admin\MenuItems;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'external' => 'required|integer',
            'url' => 'nullable|required_without:linkable_id|url',
            'linkable_id' => 'nullable|required_without:url|integer',
            'linkable_type' => [
                'nullable',
                'required_with:linkable_id',
                Rule::in([
                    'App\Models\Page',
                    'App\Models\Program', 
                    'App\Models\Article',
                    'App\Models\Album',
                    'App\Models\Event',
                    'App\Models\Grade',
                    'App\Models\JobPosting'
                ])
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['linkable_id', 'linkable_type'])) {
                $linkableType = $this->input('linkable_type');
                $linkableId = $this->input('linkable_id');
                
                if (class_exists($linkableType)) {
                    $model = new $linkableType;
                    $exists = $model->where('id', $linkableId)->exists();
                    
                    if (!$exists) {
                        $modelName = class_basename($linkableType);
                        $validator->errors()->add('linkable_id', "The selected {$modelName} does not exist.");
                    }
                } else {
                    $validator->errors()->add('linkable_type', 'The selected linkable type is invalid.');
                }
            }
        });
    }

    public function prepareForValidation()
    {
        if ($this->input('external') == 1) {
            // External link: clear linkable fields
            $this->merge([
                'linkable_id' => null,
                'linkable_type' => null,
            ]);
        } else {
            // Internal link: clear url field
            $this->merge([
                'url' => null,
            ]);
        }
    }
}
