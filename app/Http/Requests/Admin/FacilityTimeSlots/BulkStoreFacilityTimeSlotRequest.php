<?php

namespace App\Http\Requests\Admin\FacilityTimeSlots;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreFacilityTimeSlotRequest extends FormRequest
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
            'start_date'    => 'required|date_format:Y-m-d',
            'end_date'      => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'days_of_week'  => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:0,6',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'capacity'      => 'required|integer|min:0',
        ];
    }
}
