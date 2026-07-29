<?php

namespace App\Observers;

use App\Models\Calendar;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class CalendarObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Calendar $calendar): void
    {
        UploadService::freeModel($calendar);
    }

    protected function logName(): string
    {
        return 'calendars';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'start_date', 'end_date'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
