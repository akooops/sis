<?php

namespace App\Http\Requests\Admin\VisitServices;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitServiceRequest extends FormRequest
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
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:3000',
            'content' => 'required|string',

            'duration' => 'required|integer|min:0',

            'file' => 'nullable|file|image', 
            'media_id' => 'nullable|exists:media,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            $mediaId = $this->input('media_id');

            if (!$file && !$mediaId) {
                $validator->errors()->add('file', 'You must provide either an image file or select an existing media.');
                $validator->errors()->add('media_id', 'You must provide either an image file or select an existing media.');
            }

            // If media_id is provided, check if it's an image
            if ($mediaId) {
                $media = Media::find($mediaId);
                if (!$media || $media->type !== 'image') {
                    $validator->errors()->add('media_id', 'The selected media must be an image.');
                }
            }
        });
    }
}
