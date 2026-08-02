<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Country\CountryData;
use App\Data\Country\StoreCountryData;
use App\Data\Country\UpdateCountryData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Country;
use App\Models\FormBlockedCountry;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CountriesController extends ApiController
{
    public function index(): JsonResponse
    {
        $countries = QueryBuilder::for(Country::class)
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('code'),
                AllowedFilter::exact('is_enabled'),
                $this->searchTranslations(
                    ['id', 'name', 'code', 'alpha3'],
                    ['title', 'nationality'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            // Alphabetical by default: this is a reference list, not a log.
            ->defaultSort('name')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CountryData::collect($countries, PaginatedDataCollection::class), 'Countries retrieved successfully');
    }

    public function show(Country $country): JsonResponse
    {
        return $this->respond(CountryData::from($country), 'Country retrieved successfully');
    }

    public function store(StoreCountryData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $country = Country::create([
            'name' => $data->name,
            'code' => $data->code,
            'alpha3' => $data->alpha3,
            'flag' => $data->flag,
            'is_enabled' => $data->is_enabled,
            'title' => [$default => $data->title],
            'nationality' => [$default => $data->nationality],
        ]);

        if (! $data->flag_image instanceof Optional && $data->flag_image) {
            UploadService::attach($data->flag_image, $country, Country::FLAG_COLLECTION);
        }

        return $this->respond(CountryData::from($country->fresh()), 'Country created successfully', 201);
    }

    public function update(UpdateCountryData $data, Country $country): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $country->update($country->mergeTranslations(Arr::except($data->toArray(), ['flag_image'])));

        if (! $data->flag_image instanceof Optional && $data->flag_image) {
            UploadService::attach($data->flag_image, $country, Country::FLAG_COLLECTION);
        }

        return $this->respond(CountryData::from($country->fresh()), 'Country updated successfully');
    }
}
