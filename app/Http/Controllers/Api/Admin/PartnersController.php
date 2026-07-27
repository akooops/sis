<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Partner\PartnerData;
use App\Data\Partner\ReorderPartnersData;
use App\Data\Partner\StorePartnerData;
use App\Data\Partner\UpdatePartnerData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Partner;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PartnersController extends ApiController
{
    public function index(): JsonResponse
    {
        $partners = QueryBuilder::for(Partner::class)
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->search(['id', 'name', 'url']),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            // Display order by default — the list is a strip, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(PartnerData::collect($partners, PaginatedDataCollection::class), 'Partners retrieved successfully');
    }

    public function show(Partner $partner): JsonResponse
    {
        return $this->respond(PartnerData::from($partner), 'Partner retrieved successfully');
    }

    public function store(StorePartnerData $data): JsonResponse
    {
        $partner = Partner::create([
            'name' => $data->name,
            'url' => $data->url,
            'order' => Partner::nextOrder(),
        ]);

        UploadService::attach($data->logo, $partner, Partner::LOGO_COLLECTION);

        return $this->respond(PartnerData::from($partner->fresh()), 'Partner created successfully', 201);
    }

    public function update(UpdatePartnerData $data, Partner $partner): JsonResponse
    {
        $partner->update([
            'name' => $data->name,
            'url' => $data->url,
        ]);

        if (! $data->logo instanceof Optional && $data->logo) {
            UploadService::attach($data->logo, $partner, Partner::LOGO_COLLECTION);
        }

        return $this->respond(PartnerData::from($partner->fresh()), 'Partner updated successfully');
    }

    public function destroy(Partner $partner): JsonResponse
    {
        $partner->delete();

        return $this->respond(null, 'Partner deleted successfully');
    }

    /**
     * Rewrite the display order from a list of ids — position in the array is the
     * order. Builder updates, so reordering writes no audit row per partner: the
     * position is presentation, and a row per drag would drown the log.
     *
     * Ids the payload omits keep their current order, which is why the drawer
     * always submits the whole list.
     */
    public function reorder(ReorderPartnersData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            Partner::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Partners reordered successfully');
    }
}
