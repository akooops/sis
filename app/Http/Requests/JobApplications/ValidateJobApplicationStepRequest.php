<?php

namespace App\Http\Requests\JobApplications;

use App\Rules\CheckInternationalPhoneNumber;
use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class ValidateJobApplicationStepRequest extends FormRequest
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
        $step = $this->input('step', 0);
        
        switch ($step) {
            case 1:
                return $this->getStep1Rules();
            case 2:
                return $this->getStep2Rules();
            case 3:
                return $this->getStep3Rules();
            case 4:
                return $this->getStep4Rules();
            case 5:
                return $this->getStep5Rules();
            case 6:
                return $this->getStep6Rules();
            case 7:
            default:
                return $this->getAllStepsRules();
        }
    }

    /**
     * Step 1: Personal Information
     */
    protected function getStep1Rules(): array
    {
        return [
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
        ];
    }

    /**
     * Step 2: Education
     */
    protected function getStep2Rules(): array
    {
        return [
            'education' => 'required|array|min:1',
            'education.*.institution' => 'required|string|max:255',
            'education.*.degree' => 'required|string|max:255',
            'education.*.field_of_study' => 'required|string|max:255',
            'education.*.start_year' => 'required|integer|min:1950|max:2030',
            'education.*.end_year' => 'required|integer|min:1950|max:2030|gte:education.*.start_year',
            'education.*.description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Step 3: Experience
     */
    protected function getStep3Rules(): array
    {
        return [
            'experience' => 'required|array|min:1',
            'experience.*.company_name' => 'required|string|max:100',
            'experience.*.job_title' => 'required|string|max:100',
            'experience.*.start_year' => 'required|integer|min:1950|max:2030',
            'experience.*.end_year' => 'nullable|required_if:experience.*.is_current,false|integer|min:1950|max:2030|gte:experience.*.start_year',
            'experience.*.is_current' => 'required|boolean',
            'experience.*.description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Step 4: Languages
     */
    protected function getStep4Rules(): array
    {
        return [
            'languages' => 'required|array|min:1',
            'languages.*.name' => 'required|string|max:50',
            'languages.*.proficiency' => 'required|in:basic,intermediate,advanced,native',
        ];
    }

    /**
     * Step 5: Skills
     */
    protected function getStep5Rules(): array
    {
        return [
            'skills' => 'required|array|min:1',
            'skills.*' => 'string|max:255',
        ];
    }

    /**
     * Step 6: Documents
     */
    protected function getStep6Rules(): array
    {
        return [
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ];
    }

    /**
     * Step 7: All steps combined (final validation)
     */
    protected function getAllStepsRules(): array
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
