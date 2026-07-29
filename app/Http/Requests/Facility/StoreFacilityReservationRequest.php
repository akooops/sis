<?php

namespace App\Http\Requests\Facility;

use App\Models\FacilityTimeSlot;
use App\Rules\CheckInternationalPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class StoreFacilityReservationRequest extends FormRequest
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
        $facility = $this->attributes->get('facility');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',

            'phone' => [
                'required',
                'string',
                'max:20',
                new CheckInternationalPhoneNumber(),
            ],

            'guests_count' => 'required|integer|min:1|max:1000',
            'message' => 'nullable|string|max:5000',

            'facility_time_slot_id' => [
                'required',
                'exists:facility_time_slots,id',
                function ($attribute, $value, $fail) use ($facility) {
                    $timeSlot = FacilityTimeSlot::find($value);

                    if (! $timeSlot) {
                        $fail('The selected time slot does not exist.');
                        return;
                    }

                    if (! $facility || $timeSlot->facility_id !== $facility->id) {
                        $fail('The selected time slot does not belong to this facility.');
                        return;
                    }

                    if ($timeSlot->starts_at < now()) {
                        $fail('The selected time slot is in the past.');
                        return;
                    }

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
}
