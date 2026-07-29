<?php

namespace App\Http\Requests\Admin\Brands;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
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
        $brand = $this->route('brand');

        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'status' => 'required|in:draft,published',
            'order' => 'nullable|integer|min:0',

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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mediaId = $this->input('media_id');

            if ($mediaId) {
                $media = Media::find($mediaId);
                if (!$media || $media->type !== 'image') {
                    $validator->errors()->add('media_id', 'The selected media must be an image.');
                }
            }
        });
    }
}
