<?php

namespace App\Http\Requests\Admin\Menus;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMenuRequest extends FormRequest
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
        $menu = $this->route('menu');

        $validator->after(function ($validator) use ($menu){
            if ($menu->is_system_menu) {
                $validator->errors()->add('menu', 'This menu cannot be deleted as it is a system menu.');

                return;
            }
        });
    }
}
