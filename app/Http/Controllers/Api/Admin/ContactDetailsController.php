<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\ContactDetail\ContactDetailData;
use App\Data\ContactDetail\ReorderContactDetailsData;
use App\Data\ContactDetail\StoreContactDetailData;
use App\Data\ContactDetail\UpdateContactDetailData;
use App\Http\Controllers\Api\ApiController;
use App\Models\ContactDetail;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContactDetailsController extends ApiController
{
    public function index(): JsonResponse
    {
        $details = QueryBuilder::for(ContactDetail::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('type'),
                $this->searchTranslations(
                    ['id', 'name', 'value', 'platform'],
                    ['title', 'address'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'type', 'order', 'created_at'])
            // Display order by default: this is a strip in a footer, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ContactDetailData::collect($details, PaginatedDataCollection::class), 'Contact details retrieved successfully');
    }

    public function show(ContactDetail $contactDetail): JsonResponse
    {
        return $this->respond(ContactDetailData::from($contactDetail), 'Contact detail retrieved successfully');
    }

    public function store(StoreContactDetailData $data): JsonResponse
    {
        $detail = ContactDetail::create($this->forType($data->type, [
            'type' => $data->type,
            'name' => $data->name,
            'title' => [Language::defaultCode() => $data->title],
            'value' => $data->value,
            // forType() blanks this for every type that does not own it, so the
            // branch lives in one place rather than being restated here.
            'address' => [Language::defaultCode() => $data->address],
            'platform' => $data->platform,
            'map_url' => $data->map_url,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'order' => ContactDetail::nextOrder(),
        ]));

        return $this->respond(ContactDetailData::from($detail), 'Contact detail created successfully', 201);
    }

    public function update(UpdateContactDetailData $data, ContactDetail $contactDetail): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $contactDetail->update($this->forType($data->type, $contactDetail->mergeTranslations([
            'type' => $data->type,
            'name' => $data->name,
            'title' => $data->title,
            'value' => $data->value,
            'address' => $data->address,
            'platform' => $data->platform,
            'map_url' => $data->map_url,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ])));

        return $this->respond(ContactDetailData::from($contactDetail->fresh()), 'Contact detail updated successfully');
    }

    public function destroy(ContactDetail $contactDetail): JsonResponse
    {
        $contactDetail->delete();

        return $this->respond(null, 'Contact detail deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * page submits the whole list.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderContactDetailsData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            ContactDetail::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Contact details reordered successfully');
    }

    /**
     * Blank every column the chosen type does not use. Without this a row retyped
     * from address to phone keeps its coordinates and still lands on the map, and
     * a former social row keeps a platform nothing renders.
     *
     * The registry decides whether the type has a scalar `value` at all;
     * ContactDetail::extrasFor() decides which extra columns it owns — the same
     * predicate the validation rules and the form branch on.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function forType(string $type, array $attributes): array
    {
        if ((ContactDetail::typeConfig($type)['value'] ?? null) === null) {
            $attributes['value'] = null;
        }

        $extras = ContactDetail::extrasFor($type);

        if ($extras !== 'social') {
            $attributes['platform'] = null;
        }

        if ($extras !== 'address') {
            // Empty array, NOT null: spatie routes every non-array value through
            // setTranslation(), so `null` would store {"en": null} — and MySQL
            // renders that JSON null as the literal string 'null', which the
            // index search's LIKE then matches. Every phone in the table would
            // answer a search for "null".
            $attributes['address'] = [];
            $attributes['map_url'] = null;
            $attributes['latitude'] = null;
            $attributes['longitude'] = null;
        }

        return $attributes;
    }
}
