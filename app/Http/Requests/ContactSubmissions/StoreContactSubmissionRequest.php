<?php

namespace App\Http\Requests\ContactSubmissions;

use App\Rules\CheckInternationalPhoneNumber;
use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class StoreContactSubmissionRequest extends FormRequest
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
            'email' => 'required|email|max:255',

            'phone' => [
                'required',
                'string',
                'max:20',
                new CheckInternationalPhoneNumber(),
            ],

            'subject' => 'required|string|max:1000',
            'message' => 'required|string',

            'g-recaptcha-response' => ['required', new ReCaptcha()],
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
