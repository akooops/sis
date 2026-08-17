<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Audits what a PERSON does to a pool — renaming it, locking it, deleting it.
 *
 * The nightly rebuild writes these rows too, and it uses saveQuietly() precisely
 * so it does not fill the log with a row per cluster per night. The same reasoning
 * keeps Session unaudited: a machine write on a schedule is not a decision anyone
 * needs a trail of.
 */
class ClusterObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'clusters';
    }

    /**
     * 256 floats, rewritten on every rebuild, and updated() logs both sides.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['centroid', 'size', 'rebuilt_at'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
