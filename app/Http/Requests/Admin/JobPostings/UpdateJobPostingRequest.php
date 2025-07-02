<?php

namespace App\Http\Requests\Admin\JobPostings;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class UpdateJobPostingRequest extends FormRequest
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
        $jobPosting = $this->route('jobPosting');

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:job_postings,slug,'.$jobPosting->id,

            'employment_type' => 'required|string|in:full_time,part_time,internship',
            'is_remote' => 'boolean',

            'required_years_of_experience' => 'nullable|integer|min:0',
            'number_of_positions' => 'required|integer|min:1',
            'application_deadline' => 'nullable|date',
            'status' => 'required|string|in:draft,hidden,published',

            'file' => 'nullable|file|image', 
            'media_id' => 'nullable|exists:media,id',
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
