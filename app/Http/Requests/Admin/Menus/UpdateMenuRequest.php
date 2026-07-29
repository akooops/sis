<?php

namespace App\Http\Requests\Admin\Menus;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateMenuRequest extends FormRequest
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
            'facility_id' => 'nullable|exists:facilities,id',
            'name' => 'required|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $menu = $this->route('menu');

        $validator->after(function ($validator) use ($menu){
            if ($menu->is_system_menu) {
                $validator->errors()->add('menu', 'This menu cannot be updated as it is a system menu.');

                return;
            }
        });
    }
}
