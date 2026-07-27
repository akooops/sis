<?php

namespace App\Observers;

use App\Models\Partner;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class PartnerObserver extends BaseObserver
{
    /** Free the logo back into the library rather than destroying the file. */
    public function deleting(Partner $partner): void
    {
        UploadService::freeModel($partner);
    }

    protected function logName(): string
    {
        return 'partners';
    }

    /**
     * Reordering is a builder update so it never reaches an observer, but a
     * create still records order 0/1/2 — noise on a field the user never set.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'url'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
