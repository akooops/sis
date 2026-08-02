<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormBlockedIpData;
use App\Data\Form\StoreFormBlockedIpData;
use App\Http\Controllers\Api\ApiController;
use App\Models\FormBlockedIp;
use App\Services\Forms\IpValue;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The addresses and ranges one form refuses.
 *
 * Not a pivot: the row carries the literal value, so there is nothing to select
 * from and no second model to point at. Add and remove only — an entry is an
 * address plus the reason it is there, and editing the address would be a
 * different block, not the same one changed.
 *
 * Scoped by its parent the flat way, ?filter[form_id]=…, like every other
 * child resource here.
 */
class FormBlockedIpsController extends ApiController
{
    public function index(): JsonResponse
    {
        $blockedIps = QueryBuilder::for(FormBlockedIp::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('form_id'),
                AllowedFilter::exact('is_cidr'),
                $this->search(['value', 'note']),
            ])
            ->allowedSorts(['id', 'value', 'is_cidr', 'created_at'])
            // A block list is read newest-first: the last thing added is the
            // one being reasoned about.
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FormBlockedIpData::collect($blockedIps, PaginatedDataCollection::class), 'Blocked IPs retrieved successfully');
    }

    /**
     * `value` arrives canonicalised (StoreFormBlockedIpData::prepareForPipeline),
     * so is_cidr is simply whether it kept a prefix — derived here rather than
     * asked of the admin, which is the only way the flag cannot disagree with
     * the value it describes.
     */
    public function store(StoreFormBlockedIpData $data): JsonResponse
    {
        $blockedIp = FormBlockedIp::create([
            'form_id' => $data->form_id,
            'value' => $data->value,
            'is_cidr' => IpValue::isCidr($data->value),
            'note' => $data->note,
        ]);

        return $this->respond(FormBlockedIpData::from($blockedIp), 'Address blocked successfully', 201);
    }

    public function destroy(FormBlockedIp $formBlockedIp): JsonResponse
    {
        $formBlockedIp->delete();

        return $this->respond(null, 'Address unblocked successfully');
    }
}
