<?php

namespace App\Http\Requests\Admin\ProgramStreams;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramStreamRequest extends FormRequest
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
            'slug' => 'required|string|max:500|unique:program_streams,slug',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'title' => 'required|string|max:1000',
            'description' => 'required|string|max:3000',
            'content' => 'required|string',
            'cta' => 'required|string|max:255',
        ];
    }
}
