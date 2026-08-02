<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormBlockedCountryData;
use App\Data\Form\StoreFormBlockedCountryData;
use App\Http\Controllers\Api\ApiController;
use App\Models\FormBlockedCountry;
use App\Services\Forms\GeoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

/**
 * The countries a form refuses. Flat and scoped by filter[form_id], like every
 * other pivot resource here; there is no update, because a block is a form and
 * a country and nothing else.
 */
class FormBlockedCountriesController extends ApiController
{
    public function index(): JsonResponse
    {
        $query = QueryBuilder::for(FormBlockedCountry::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('form_id'),
                AllowedFilter::exact('country_id'),
                $this->searchRelationByColumns('country', ['id', 'name', 'code', 'alpha3']),
            ])
            ->allowedIncludes(['form', 'country'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at');

        // allowedIncludes applies a plain with(); re-declaring the SAME relation
        // afterwards replaces that closure with a constrained one — which is
        // what makes flag_url one query instead of one per row.
        $includes = QueryBuilderRequest::fromRequest(request())->includes();

        if ($includes->contains('country')) {
            $query->with(['country' => fn ($country) => $country->with('media')]);
        }

        if ($includes->contains('form')) {
            $query->with(['form' => fn ($form) => $form->withCount(['pages', 'fields'])]);
        }

        $links = $query->paginate($this->perPage())->appends(request()->query());

        return $this->respond(FormBlockedCountryData::collect($links, PaginatedDataCollection::class), 'Blocked countries retrieved successfully');
    }

    /**
     * Through the pivot MODEL, never $form->blockedCountries()->attach(): the
     * table carries its own ULID primary key with no database default, so a raw
     * pivot insert fails on it — and only a model write fires the observer that
     * audits the block against the form.
     */
    public function store(StoreFormBlockedCountryData $data): JsonResponse
    {
        $links = collect($data->countries)->map(fn (string $countryId) => FormBlockedCountry::create([
            'form_id' => $data->form_id,
            'country_id' => $countryId,
        ]));

        $links->each->load('country.media');

        return $this->respond(FormBlockedCountryData::collect($links->all()), 'Countries blocked successfully', 201);
    }

    public function destroy(FormBlockedCountry $formBlockedCountry): JsonResponse
    {
        $formBlockedCountry->delete();

        return $this->respond(null, 'Country unblocked successfully');
    }

    /**
     * Whether a country block can actually do anything.
     *
     * GeoResolver reads the visitor's country from a CDN header and trusts it
     * ONLY behind a recognised proxy — with TRUSTED_PROXIES unset the resolver
     * always returns null and every block silently passes everyone through.
     * The admin has no way to see that from the list, so the UI asks.
     */
    public function geoStatus(Request $request, GeoResolver $geo): JsonResponse
    {
        return $this->respond([
            'configured' => $geo->isConfigured($request),
            'headers' => array_values((array) config('forms.geo.country_headers', [])),
        ], 'Geo status retrieved successfully');
    }
}
