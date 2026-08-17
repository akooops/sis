<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Translation\TranslationKeyData;
use App\Http\Controllers\Api\ApiController;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Read-only registry mirroring TranslationKeysSeeder::catalogue(), grown
 * by reseeding.
 * Values live elsewhere — a key plus a locale is TranslationsController.
 */
class TranslationKeysController extends ApiController
{
    public function __construct(protected TranslationService $translations) {}

    public function index(): JsonResponse
    {
        $keys = QueryBuilder::for(TranslationKey::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('group'),
                $this->search(['id', 'group', 'key']),
            ])
            ->allowedSorts(['id', 'group', 'key', 'created_at'])
            ->defaultSort('group')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(TranslationKeyData::collect($keys, PaginatedDataCollection::class), 'Translation keys retrieved successfully');
    }

    /** The groups (lang file names) the registry covers — drives the group filter. */
    public function groups(): JsonResponse
    {
        return $this->respond($this->translations->groups(), 'Translation groups retrieved successfully');
    }
}
