<?php

namespace App\Http\Requests\Admin\VisitTimeSlots;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateVisitTimeSlotRequest extends FormRequest
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
            'starts_at' => 'required|date_format:Y-m-d H:i',
            'ends_at' => 'required|date_format:Y-m-d H:i|after_or_equal:starts_at',      
        ];
    }
}
