<?php

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMediaRequest extends FormRequest
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
        $media = $this->route('media');

        $validator->after(function ($validator) use ($media){
            if ($media->isUsed()) {
                $validator->errors()->add('media', 'This media cannot be deleted as it is in use.');

                return;
            }
        });
    }
}
