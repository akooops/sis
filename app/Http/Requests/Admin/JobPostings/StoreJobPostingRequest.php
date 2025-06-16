<?php

namespace App\Http\Requests\Admin\JobPostings;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
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
            'slug' => 'required|string|max:500|unique:job_postings,slug',
            
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:3000',
            'content' => 'required|string',
            'required_skills' => 'required|string|max:2000',

            'employment_type' => 'required|string|in:full_time,part_time,internship',
            'is_remote' => 'boolean',
            
            'required_years_of_experience' => 'nullable|integer|min:0',
            'number_of_positions' => 'required|integer|min:1',
            'application_deadline' => 'nullable|date|after:today',

            'status' => 'required|string|in:draft,hidden,published',
        ];
    }
}
