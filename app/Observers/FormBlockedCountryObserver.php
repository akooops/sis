<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/** Blocking a country is an activity on the form, not on a link nobody opens. */
class FormBlockedCountryObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'forms';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->form;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->country;
    }
}
