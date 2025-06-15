<?php

namespace App\Http\Requests\Admin\VisitTimeSlots;

use Illuminate\Foundation\Http\FormRequest;

class DeleteVisitTimeSlotRequest extends FormRequest
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
        $visitTimeSlot = $this->route('visitTimeSlot');

        $validator->after(function ($validator) use ($visitTimeSlot){
            if ($visitTimeSlot->reserved) {
                $validator->errors()->add('visit_time_slot', 'This time slots have been already reserved.');

                return;
            }
        });
    }
}
