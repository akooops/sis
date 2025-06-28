<?php

namespace App\Traits;

use App\Models\Language;
use App\Models\Translation;

trait Translatable
{
    protected static function bootHasTranslations()
    {
        static::deleting(function ($model) {
            $model->translations()->delete();
        });
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function getTranslation(string $field, string $languageCode, bool $fallbackToDefault = true)
    {
        $language = Language::where('code', $languageCode)->first();

        if (!$language) {
            return "{$field}.{$languageCode}";
        }

        $translation = $this->translations()
            ->where('field', $field)
            ->where('language_id', $language->id)
            ->value('value');

        if ($translation) {
            return $translation;
        }

        return "{$field}.{$languageCode}";
    }

    public function getLocalTranslation(string $field)
    {
        $languageCode = app()->getLocale();

        $language = Language::where('code', $languageCode)->first();

        if (!$language) {
            return "{$field}.{$languageCode}";
        }

        $translation = $this->translations()
            ->where('field', $field)
            ->where('language_id', $language->id)
            ->value('value');

        if ($translation) {
            return $translation;
        }

        return "{$field}.{$languageCode}";
    }


    public function setTranslation(string $field, string $languageCode, $value)
    {
        if(is_null($value)) return;

        $language = Language::where('code', $languageCode)->first();
        if(!$language) return;

        $this->translations()->updateOrCreate(
            [
                'field' => $field,
                'language_id' => $language->id
            ],
            ['value' => $value]
        );
    }

    public function removeTranslation(string $field, string $languageCode)
    {
        $language = Language::where('code', $languageCode)->first();
        
        if ($language) {
            $this->translations()
                ->where('field', $field)
                ->where('language_id', $language->id)
                ->delete();
        }
    }

    public function getTranslations(string $field): array
    {
        return $this->translations()
            ->where('field', $field)
            ->with('language')
            ->get()
            ->mapWithKeys(fn ($translation) => [
                $translation->language->code => $translation->value
            ])
            ->toArray();
    }

    public function hasTranslation(string $field, string $languageCode): bool
    {
        return $this->translations()
            ->whereHas('language', fn ($q) => $q->where('code', $languageCode))
            ->where('field', $field)
            ->exists();
    }

    public function getTranslatableFieldsByLanguages(): array
    {
        $translatableFields = $this->getTranslatableFields();
        $result = [];

        foreach ($translatableFields as $field) {
            $result[$field] = $this->getTranslations($field);
        }

        return $result;
    }

    abstract public function getTranslatableFields(): array;
}
