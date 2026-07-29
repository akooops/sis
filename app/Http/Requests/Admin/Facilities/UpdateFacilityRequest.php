<?php

namespace App\Http\Requests\Admin\Facilities;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityRequest extends FormRequest
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
        $facility = $this->route('facility');

        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('facilities', 'slug')->ignore($facility->id)],
            'domain' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('facilities', 'domain')->ignore($facility->id)],
            'status' => 'required|in:draft,published',
            'order' => 'nullable|integer|min:0',

            'theme' => 'nullable|array',
            'theme.primary_color' => 'nullable|string|max:20',
            'theme.secondary_color' => 'nullable|string|max:20',

            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',

            'socials' => 'nullable|array',
            'socials.*' => 'nullable|string|max:500',

            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',

            'logo' => 'nullable|file|image',
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
