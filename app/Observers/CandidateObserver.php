<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class CandidateObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'candidates';
    }

    /**
     * The embedding is 256 floats and the summary is a paragraph; updated() writes
     * BOTH sides of a diff, so logging either would put a wall of machine output in
     * every audit row and bury the change a human actually made.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['embedding', 'embedded_at', 'ai_comment', 'summarised_at'];
    }

    /**
     * The record is gone, so name what is worth keeping: enough to recognise the
     * person in the trail without holding their whole profile there.
     *
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => trim("{$model->first_name} {$model->last_name}")];
    }
}
