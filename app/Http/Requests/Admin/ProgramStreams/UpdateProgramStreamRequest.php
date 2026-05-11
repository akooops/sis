<?php

namespace App\Http\Requests\Admin\ProgramStreams;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramStreamRequest extends FormRequest
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
        $programStream = $this->route('programStream');

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:500|unique:program_streams,slug,'.$programStream->id,
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }
}
