<?php

namespace App\Http\Requests\Admin\Brands;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreBrandRequest extends FormRequest
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
            'slug' => 'required|string|max:255|alpha_dash|unique:brands,slug',
            'status' => 'required|in:draft,published',
            'order' => 'nullable|integer|min:0',

            'title' => 'required|string|max:1000',
            'tagline' => 'nullable|string|max:1000',
            'description' => 'required|string|max:3000',
            'content' => 'required|string',

            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',

            'assets' => 'nullable|array',
            'assets.*.id' => 'nullable|exists:brand_assets,id',
            'assets.*.file_id' => 'nullable|exists:files,id',
            'assets.*.name' => 'required|string|max:255',
            'assets.*.group' => 'required|in:logos,colors,fonts,guidelines,audio,images,documents',
            'assets.*.order' => 'nullable|integer|min:0',
        ];
    }

    protected function prepareForValidation()
    {
        if (! $this->input('slug') && $this->input('name')) {
            $this->merge([
                'slug' => Str::slug($this->input('name')),
            ]);
        }
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

            if ($mediaId) {
                $media = Media::find($mediaId);
                if (!$media || $media->type !== 'image') {
                    $validator->errors()->add('media_id', 'The selected media must be an image.');
                }
            }
        });
    }
}
