<?php

namespace App\Http\Requests\Admin\Calendars;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarRequest extends FormRequest
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
            'starts_at' => 'required|date|date_format:Y-m-d',
            'ends_at' => 'required|date|after:starts_at|date_format:Y-m-d',
            'is_active' => 'required|boolean',
            'file' => 'required|file'
        ];
    }
}
