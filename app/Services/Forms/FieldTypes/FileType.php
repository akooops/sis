<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use App\Rules\CleanUpload;

/**
 * A file upload. The submitted value is a media id (or a list of them), never
 * the bytes — the file is uploaded to its own endpoint first.
 *
 * The admin picks EXTENSIONS, not just the four broad categories, because "PDF
 * only" is the common ask and "documents" would also accept a .zip. The
 * categories CleanUpload needs are derived from whatever extensions were picked.
 */
class FileType extends BaseFieldType
{
    public function code(): string
    {
        return 'file';
    }

    public function label(): string
    {
        return 'File upload';
    }

    public function icon(): string
    {
        return 'ki-file-up';
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'is_multiple', label: 'Allow several files', type: 'switch', default: false),
            new FieldData(key: 'max_files', label: 'Maximum files', type: 'number', default: 3),
            // A multiselect, NOT a tags box: the list is fixed by
            // config('uploads.allowed_types'), so the admin picks from it rather
            // than typing an extension the uploader would refuse anyway.
            new FieldData(
                key: 'extensions',
                label: 'Accepted file types',
                type: 'multiselect',
                options: static::extensionOptions(),
                help: 'Leave empty to accept every configured type.',
            ),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $categories = $this->categories($field);

        if ($this->setting($field, 'is_multiple')) {
            $max = (int) ($this->setting($field, 'max_files') ?? 3);

            return [
                '' => [$this->presence($field), 'array', 'max:'.max(1, $max)],
                '.*' => ['string', new CleanUpload($categories)],
            ];
        }

        return ['' => [$this->presence($field), 'string', new CleanUpload($categories)]];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        if ($this->setting($field, 'is_multiple')) {
            return is_array($value) ? array_values(array_filter($value)) : [];
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Extensions the admin allowed, or every configured one. */
    public function extensions(FormField $field): array
    {
        $chosen = $this->setting($field, 'extensions');

        if (! is_array($chosen) || $chosen === []) {
            return array_values(array_unique(array_merge(...array_values(config('uploads.allowed_types', [])))));
        }

        return array_values(array_map('strtolower', $chosen));
    }

    /**
     * The upload categories covering the chosen extensions — CleanUpload speaks
     * categories, and narrowing to exact extensions happens at the endpoint.
     *
     * @return array<int, string>
     */
    protected function categories(FormField $field): array
    {
        $extensions = $this->extensions($field);
        $categories = [];

        foreach (config('uploads.allowed_types', []) as $category => $allowed) {
            if (array_intersect($extensions, array_map('strtolower', $allowed)) !== []) {
                $categories[] = $category;
            }
        }

        return $categories ?: array_keys(config('uploads.allowed_types', []));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected static function extensionOptions(): array
    {
        $options = [];

        foreach (config('uploads.allowed_types', []) as $category => $allowed) {
            foreach ($allowed as $extension) {
                $extension = strtolower(trim($extension));
                $options[$extension] = ['value' => $extension, 'label' => strtoupper($extension).' — '.$category];
            }
        }

        ksort($options);

        return array_values($options);
    }
}
