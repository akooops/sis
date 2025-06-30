<?php

namespace App\Http\Requests\Admin\Grades;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class OrderGradesRequest extends FormRequest
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
            'order' => 'required|array',
            'order.*.id' => 'required|exists:grades,id',
            'order.*.order' => 'required|integer',
        ];
    }
}
