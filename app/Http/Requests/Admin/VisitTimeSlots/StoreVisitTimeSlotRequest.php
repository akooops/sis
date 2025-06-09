<?php

namespace App\Http\Requests\Admin\VisitTimeSlots;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitTimeSlotRequest extends FormRequest
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
            'starts_at' => 'required|date_format:Y-m-d H:i',
            'ends_at' => 'required|date_format:Y-m-d H:i|after_or_equal:ends_at',      
        ];
    }
}
