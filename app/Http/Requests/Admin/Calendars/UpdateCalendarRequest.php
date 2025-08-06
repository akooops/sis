<?php

namespace App\Http\Requests\Admin\Calendars;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateCalendarRequest extends FormRequest
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
            'starts_at' => 'required|date|date_format:Y-m-d',
            'ends_at' => 'required|date|after:starts_at|date_format:Y-m-d',
            'is_active' => 'required|boolean',
            'file' => 'nullable|file'
        ];
    }
}
