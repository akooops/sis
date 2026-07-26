<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Translation\TranslationLineData;
use App\Data\Translation\UpdateTranslationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * One locale's translations. The keys are paginated from the database so search,
 * sorting and filters work like every other index; each row's value is then read
 * out of lang/{code}/{group}.php, which is the file __() itself reads. Nothing is
 * ever stored in a translations column.
 */
class TranslationsController extends ApiController
{
    public function __construct(protected TranslationService $translations) {}

    public function index(Language $language): JsonResponse
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

        $this->translations->hydrate($keys->getCollection(), $language->code);

        return $this->respond(TranslationLineData::collect($keys, PaginatedDataCollection::class), 'Translations retrieved successfully');
    }

    public function update(UpdateTranslationData $data, Language $language, TranslationKey $translationKey): JsonResponse
    {
        try {
            $this->translations->put($language->code, $translationKey->group, $translationKey->key, $data->value);
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw ValidationException::withMessages(['value' => $e->getMessage()]);
        }

        $translationKey->withLine(
            $language->code,
            $this->translations->get($language->code, $translationKey->group, $translationKey->key),
        );

        return $this->respond(TranslationLineData::from($translationKey), 'Translation updated successfully');
    }
}
