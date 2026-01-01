<?php

namespace App\Http\Requests\Admin\Partners;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
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
            'url' => 'nullable|url|max:500',
            'file' => 'nullable|file|image', 
            'media_id' => 'nullable|exists:media,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            $mediaId = $this->input('media_id');

            // If neither file nor media_id is provided, and this is an update, it's okay (keep existing image)
            // But if both are provided, validate media_id
            if ($mediaId) {
                $media = Media::find($mediaId);
                if (!$media || $media->type !== 'image') {
                    $validator->errors()->add('media_id', 'The selected media must be an image.');
                }
            }
        });
    }
}

