<?php

namespace App\Http\Requests\Admin\Settings;

use App\Rules\Cms\CheckSetting;
use App\Rules\ValidateThemeSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function rules()
    {
        $setting = request()->route('setting');
        $type = $setting->type;
        $options = $setting->options;

        $baseRule = ['required'];

        $typeRules = match($type) {
            'text', 'rich_text', 'code' => ['string'],
            'password' => ['string', 'min:8'],
            'color' => ['regex:/^#[a-f0-9]{6}$/i'],
            'number' => ['numeric'],
            'boolean' => ['boolean'],
            'select' => $options ? ['string', 'in:' . implode(',', $options['choices'] ?? [])] : ['string'],
            'multi-select' => ['array'],
            'date' => ['date_format:Y-m-d'],
            'time' => ['date_format:H:i'],
            'datetime' => ['date', 'date_format:Y-m-d H:i'],
            'array' => ['string'],
            'media' => ['exists:media,id'],
            'article' => ['exists:articles,id'],
            'page' => ['exists:pages,id'],
            'menu' => ['exists:menus,id'],
            'album' => ['exists:albums,id'],
            'event' => ['exists:events,id'],
            default => ['string'],
        };

        return [
            'value' => array_merge($baseRule, $typeRules)
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $setting = request()->route('setting');
            
            // Validate JSON for array types
            if ($setting->type === 'array') {
                $value = $this->input('value');
                if (!is_string($value) || !json_decode($value, true)) {
                    $validator->errors()->add('value', 'The value must be a valid JSON array.');
                }
            }
        });
    }
}