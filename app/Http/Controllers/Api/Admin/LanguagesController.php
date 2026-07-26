<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Language\LanguageData;
use App\Data\Language\StoreLanguageData;
use App\Data\Language\UpdateLanguageData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The locales the app holds translations for. The lang/{code}/ folder is created,
 * renamed and left behind by LanguageObserver — nothing here touches the disk.
 */
class LanguagesController extends ApiController
{
    public function index(): JsonResponse
    {
        $languages = QueryBuilder::for(Language::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_enabled'),
                AllowedFilter::exact('is_default'),
                AllowedFilter::exact('is_rtl'),
                $this->search(['id', 'name', 'code']),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(LanguageData::collect($languages, PaginatedDataCollection::class), 'Languages retrieved successfully');
    }

    public function show(Language $language): JsonResponse
    {
        return $this->respond(LanguageData::from($language), 'Language retrieved successfully');
    }

    public function store(StoreLanguageData $data): JsonResponse
    {
        $language = Language::create([
            'name' => $data->name,
            'code' => $data->code,
            'is_default' => $data->is_default,
            'is_rtl' => $data->is_rtl,
            'is_enabled' => $data->is_enabled,
        ]);

        if (! $data->flag instanceof Optional && $data->flag) {
            UploadService::attach($data->flag, $language, 'flag');
        }

        return $this->respond(LanguageData::from($language->fresh()), 'Language created successfully', 201);
    }

    public function update(UpdateLanguageData $data, Language $language): JsonResponse
    {
        $language->update(collect($data->toArray())->except('flag')->all());

        if (! $data->flag instanceof Optional && $data->flag) {
            UploadService::attach($data->flag, $language, 'flag');
        }

        return $this->respond(LanguageData::from($language->fresh()), 'Language updated successfully');
    }

    public function destroy(Language $language): JsonResponse
    {
        if ($language->is_default) {
            throw ValidationException::withMessages([
                'is_default' => 'The default language cannot be deleted. Make another language the default first.',
            ]);
        }

        if ($language->code === config('app.fallback_locale')) {
            throw ValidationException::withMessages([
                'code' => "The fallback locale ({$language->code}) cannot be deleted.",
            ]);
        }

        $language->delete();

        return $this->respond(null, 'Language deleted successfully. Its lang folder was left on disk.');
    }
}
