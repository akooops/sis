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
            'capacity' => 'required|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $visitTimeSlot = $this->route('visitTimeSlot');

        $validator->after(function ($validator) use ($visitTimeSlot){
            if ($visitTimeSlot->visitBookings()->sum('visitors_count') > $this->input('capacity')) {
                $validator->errors()->add('capacity', 'This time slot bookings visitor count exceeds the new capacity');

                return;
            }
        });
    }
}
