<?php

namespace App\Http\Requests\VisitBookings;

use App\Rules\CheckInternationalPhoneNumber;
use App\Rules\ReCaptcha;
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

            'visitors_count' => "required|integer|min:1",
            'visit_time_slot_id' => [
                'required',
                'exists:visit_time_slots,id',
                function ($attribute, $value, $fail) use ($visitService) {
                    $timeSlot = \App\Models\VisitTimeSlot::find($value);
                    
                    if (!$timeSlot) {
                        $fail('The selected time slot does not exist.');
                        return;
                    }
                    
                    // Check if time slot belongs to the service
                    if ($timeSlot->visit_service_id !== $visitService->id) {
                        $fail('The selected time slot does not belong to the chosen service.');
                        return;
                    }
                    
                    // Check if time slot is in the past
                    if ($timeSlot->starts_at < now()) {
                        $fail('The selected time slot is in the past.');
                        return;
                    }
                            
                    // Check if there still a booking left in this time slot
                    if ($timeSlot->reserved) {
                        $fail('The selected time slot is fully booked. Please choose another time slot.');
                    }
                }
            ],
        ];
    }

    
    protected function prepareForValidation()
    {
        if ($this->has('phone') && !empty($this->input('phone'))) {
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
