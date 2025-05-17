<?php

namespace App\Http\Requests\Admin\Albums;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateAlbumRequest extends FormRequest
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
     */
    public function rules(): array
    {        
        $album = $this->route('album');

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:albums,slug,'.$album->id,
            'status' => 'required|string|in:draft,hidden,published',
            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',
            'files' => 'nullable|array',
            'files.*' => 'exists:files,id'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mediaId = $this->input('media_id');

            // Validate media type if media_id is provided
            if ($mediaId) {
                $media = Media::find($mediaId);
                
                if (!$media) {
                    $validator->errors()->add('media_id', 'The selected media does not exist.');
                } elseif ($media->type !== 'image') {
                    $validator->errors()->add('media_id', 'The selected media must be an image.');
                }
            }
        });
    }
}
