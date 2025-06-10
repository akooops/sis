<?php

namespace App\Http\Requests\VisitBookings;

use App\Rules\CheckInternationalPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class StoreVisitBookingRequest extends FormRequest
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
        $visitService = request()->route('visitService');

        return [
            'guardian_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',

            'phone' => [
                'required',
                'string',
                'max:20',
                new CheckInternationalPhoneNumber(),
            ],

            'student_name' => 'required|string|max:255',
            'student_grade' => 'required|string|max:255',
            'student_school' => 'required|string|max:255',

            'visitors_count' => "required|integer|min:1|max:{$visitService->capacity}",
            'visit_time_slot_id' => [
                'required',
                'exists:visit_time_slots,id',
                function ($attribute, $value, $fail) use ($visitService) {
                    $timeSlot = \App\Models\VisitTimeSlot::find($value);
                    if ($timeSlot && $timeSlot->visit_service_id !== $visitService->id) {
                        $fail('The selected time slot does not belong to the chosen service.');
                    }
                },
            ],
        ];
    }

    
    protected function prepareForValidation()
    {
        if ($this->has('phone')) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($this->input('phone'), null);
                if ($phoneUtil->isValidNumber($phoneNumber)) {
                    $this->merge([
                        'phone' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164)
                    ]);
                }
            } catch (NumberParseException $e) {
                
            }
        }
    }

    protected function throwValidationError($field, $message){
        $response = [
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed.',
            'details' => '',
            'errors' => [
                [
                    'field' => $field,
                    'message' => $message
                ]
            ]
        ];

        throw new HttpResponseException(response()->json($response, 422));
    }
}
