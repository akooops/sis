<?php

namespace App\Http\Requests\JobApplications;

use App\Rules\CheckInternationalPhoneNumber;
use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class StoreJobApplicationRequest extends FormRequest
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
            // Personal Information
            'personal.first_name' => 'required|string|max:255',
            'personal.last_name' => 'required|string|max:255',
            'personal.email' => 'required|email|max:255',
            'personal.phone' => [
                'required',
                'string',
                'max:20',
                new CheckInternationalPhoneNumber(),
            ],
            'personal.nationality' => 'required|string|max:255',
            'personal.address' => 'required|string|max:500',
            
            // Education
            'education' => 'required|array|min:1',
            'education.*.institution' => 'required|string|max:255',
            'education.*.degree' => 'required|string|max:255',
            'education.*.field_of_study' => 'required|string|max:255',
            'education.*.start_year' => 'required|integer|min:1950|max:2030',
            'education.*.end_year' => 'required|integer|min:1950|max:2030|gte:education.*.start_year',
            'education.*.description' => 'nullable|string|max:500',
            
            // Experience
            'experience' => 'required|array|min:1',
            'experience.*.company_name' => 'required|string|max:100',
            'experience.*.job_title' => 'required|string|max:100',
            'experience.*.start_year' => 'required|integer|min:1950|max:2030',
            'experience.*.end_year' => 'nullable|required_if:experience.*.is_current,false|integer|min:1950|max:2030|gte:experience.*.start_year',
            'experience.*.is_current' => 'required|in:true,false',
            'experience.*.description' => 'nullable|string|max:500',
            
            // Languages
            'languages' => 'required|array|min:1',
            'languages.*.name' => 'required|string|max:50',
            'languages.*.proficiency' => 'required|in:basic,intermediate,advanced,native',
            
            // Skills
            'skills' => 'required|array|min:1',
            'skills.*' => 'string|max:255',
            
            // Documents
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ];
    }
    
    protected function prepareForValidation()
    {
        if ($this->has('personal.phone') && !empty($this->input('personal.phone'))) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($this->input('personal.phone'), null);
                if ($phoneUtil->isValidNumber($phoneNumber)) {
                    $this->merge([
                        'personal.phone' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164)
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
