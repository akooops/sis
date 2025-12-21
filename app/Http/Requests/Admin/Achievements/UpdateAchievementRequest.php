<?php

namespace App\Http\Requests\Admin\Achievements;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $achievement = $this->route('achievement');
        return [
            'achievement_category_id' => 'required|exists:achievement_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievements,slug,' . $achievement->id,
            'achievement_date' => 'required|date',
            'status' => 'required|in:draft,published,hidden',
            'linkable_type' => 'nullable|string',
            'linkable_id' => 'nullable|integer',
            'url' => 'nullable|url|max:255',
            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',
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
