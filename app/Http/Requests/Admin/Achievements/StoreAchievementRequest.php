<?php

namespace App\Http\Requests\Admin\Achievements;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievements,slug',
            'achievement_date' => 'required|date',
            'status' => 'required|in:draft,published,hidden',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'done_by' => 'required|string|max:255',
            'linkable_type' => [
                'nullable',
                'required_with:linkable_id',
                Rule::in([
                    'App\Models\Page',
                    'App\Models\Program', 
                    'App\Models\Article',
                    'App\Models\Album',
                    'App\Models\Event',
                    'App\Models\JobPosting'
                ])
            ],
            'linkable_id' => 'nullable|integer',
            'url' => 'nullable|url|max:255',
            'file' => 'nullable|file|image',
            'media_id' => 'nullable|exists:media,id',
            'achievement_category_id' => 'required|exists:achievement_categories,id',
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
