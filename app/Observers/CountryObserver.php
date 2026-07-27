<?php

namespace App\Observers;

use App\Models\Country;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class CountryObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Country $country): void
    {
        UploadService::freeModel($country);
    }

    protected function logName(): string
    {
        return 'countries';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
