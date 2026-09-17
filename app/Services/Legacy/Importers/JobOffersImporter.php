<?php

namespace App\Services\Legacy\Importers;

use App\Models\JobOffer;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * `job_postings` → `job_offers`.
 *
 * WORK MODE IS THE INTERESTING ONE. The old schema asked a yes/no question,
 * `is_remote`; this app asks a three-way one — onsite, hybrid, remote — because a
 * school hires drivers and caterers as well as teachers and "not remote" is not
 * the same claim as "on site". A legacy true becomes `remote` and a legacy false
 * becomes `onsite`; nothing can become `hybrid`, because the old data genuinely
 * never recorded it and inventing it would be worse than leaving it to an editor.
 *
 * `required_skills` was a translated free-text field and lands on the translated
 * `skills` column, which is the same shape. `number_of_positions` has no column
 * here and is reported.
 *
 * `is_system` is never set from the legacy row: the one system posting in this app
 * is the seeded general application, and the old app had no such concept, so an
 * imported posting is always an ordinary one.
 */
class JobOffersImporter extends ContentImporter
{
    public function module(): string
    {
        return 'jobs';
    }

    public function describe(): string
    {
        return 'Job postings → job offers';
    }

    protected function source(): string
    {
        return 'job_postings';
    }

    protected function target(): string
    {
        return JobOffer::class;
    }

    protected function legacyClass(): string
    {
        return 'App\Models\JobPosting';
    }

    protected function translated(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'content' => 'content',
            'required_skills' => 'skills',
        ];
    }

    protected function thumbnailCollection(): ?string
    {
        return JobOffer::THUMBNAIL_COLLECTION;
    }

    protected function extra(object $row, Model $model): void
    {
        $model->employment_type = $this->enum(
            'employment_type',
            $row->employment_type ?? null,
            JobOffer::EMPLOYMENT_TYPES,
            'full_time',
        );

        $model->work_mode = ! empty($row->is_remote) ? 'remote' : 'onsite';

        $model->experience_years = $row->required_years_of_experience !== null
            ? (int) $row->required_years_of_experience
            : null;

        $model->deadline_at = $row->application_deadline ?: null;

        // Never promote an imported posting to the seeded general application.
        $model->is_system = (bool) $model->is_system;
    }
}
