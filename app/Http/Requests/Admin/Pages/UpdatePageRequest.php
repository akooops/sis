<?php

namespace App\Http\Requests\Admin\Pages;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
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
        $page = request()->route('page');

        $data = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:pages,slug,'.$page->id,
            'status' => 'required|string|in:draft,hidden,published',
            'menu_id' => 'nullable|exists:menus,id',
            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',
        ];

        if($page->is_system_page){
            unset($data['slug']);
        }

        return $data;
    }

        /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mediaId = $this->input('media_id');
            $page = request()->route('page');

            // Validate media type if media_id is provided
            if ($mediaId && !$page->is_system_page) {
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
