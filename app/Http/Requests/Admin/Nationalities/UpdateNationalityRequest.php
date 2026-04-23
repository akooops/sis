<?php

namespace App\Http\Requests\Admin\Nationalities;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNationalityRequest extends FormRequest
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
        $nationality = $this->route('nationality');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|size:2|unique:nationalities,code,'.$nationality->id,
        ];
    }
}
