<?php

namespace App\Services\Legacy\Importers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Forms\SubmissionValidator;
use App\Services\Legacy\LegacyImporter;

/**
 * `contact_submissions` and `inquiries` → FormSubmission rows.
 *
 * THEY WERE ALWAYS SUBMISSIONS. The old app gave each of these two forms a typed
 * table of its own, which is why adding a third form meant a migration; this app
 * has one submissions table and a builder, and the two forms are seeded and
 * `is_system`. So the honest destination for the history is the place an admin
 * already goes to read a contact message — Forms → Submissions — rather than two
 * tables this app would otherwise have to grow back.
 *
 * Every row comes across as `completed` with a spam score of zero, because it is
 * a message a human already read and acted on; scoring old data against today's
 * spam rules would retroactively hide real enquiries behind a fake confirmation.
 *
 * The telemetry columns stay null. The old app collected none of it, and a zero
 * is not the same claim as "unknown": `duration_seconds = 0` would mean somebody
 * filled the form instantly, which is precisely the signal the spam rules read.
 *
 * `fields` gets a snapshot of the form AS IT IS NOW, taken through the real
 * SubmissionValidator so the shape cannot drift from what the drawer expects.
 * That is the best available answer — the old rows have no snapshot of their own
 * — and it is correct today because both forms are system forms whose structure
 * is frozen.
 */
class SubmissionsImporter extends LegacyImporter
{
    public function module(): string
    {
        return 'submissions';
    }

    public function describe(): string
    {
        return 'Contact messages and admissions inquiries → form submissions';
    }

    public function sources(): array
    {
        return [];
    }

    public function available(): bool
    {
        foreach (array_keys(config('legacy.submissions', [])) as $table) {
            if ($this->c->db->has($table)) {
                return true;
            }
        }

        return false;
    }

    public function run(): void
    {
        foreach (config('legacy.submissions', []) as $table => $spec) {
            if (! $this->c->db->has($table)) {
                continue;
            }

            $form = Form::query()->where('slug', $spec['form'])->first();

            if (! $form) {
                $this->c->warn(
                    "The `{$spec['form']}` form is not seeded, so `{$table}` was skipped. "
                    .'Run `php artisan db:seed --class=FormsSeeder` and import again.'
                );

                continue;
            }

            $snapshot = $this->snapshot($form);

            $this->each($table, function (object $row) use ($table, $spec, $form, $snapshot) {
                $model = $this->model(FormSubmission::class, $table, (int) $row->id);

                $model->form_id = $form->id;
                $model->status = 'completed';
                // Through setJson: both columns are native JSON, whose key order
                // MySQL normalises, so a plain assignment makes the row dirty on
                // every single run.
                $this->setJson($model, 'data', $this->answers($row, $spec['fields']));
                $this->setJson($model, 'fields', $snapshot);

                $model->submitted_at = $row->created_at;
                $model->created_at = $row->created_at ?? $model->created_at;

                // Defaults spelled out rather than left to the column, so the row
                // says "nothing was measured" instead of "measured as zero".
                $model->spam_score = 0;
                $model->spam_reasons = null;
                $model->is_honeypot_triggered = false;
                $model->validation_error_count = 0;
                $model->submit_attempts = 1;
                $model->back_navigations = 0;
                $model->click_count = 0;
                $model->paste_count = 0;
                $model->pages_completed = $form->pages()->count();

                $this->save($model, $table, (int) $row->id);
            });
        }
    }

    /**
     * Legacy columns as the form's own answer keys.
     *
     * A phone goes through the app's formatter, because every downstream reader —
     * the CSV, a projector, a dedupe — expects E164, and the old app stored
     * whatever was typed.
     *
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    protected function answers(object $row, array $fields): array
    {
        $out = [];

        foreach ($fields as $column => $key) {
            $value = $row->{$column} ?? null;

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $out[$key] = $key === 'phone' ? $this->phone($value) : trim((string) $value);
        }

        return $out;
    }

    /**
     * The {key: {label, type, order}} map, built by the app's own snapshotter.
     *
     * Reused rather than reimplemented because that shape has a subtlety worth
     * inheriting: it carries an explicit integer `order`, since MySQL's JSON type
     * normalises object keys and throws away the order they went in as.
     */
    protected function snapshot(Form $form): array
    {
        $fields = $form->topLevelFields()->with(['page', 'children'])->get();

        return app(SubmissionValidator::class)->snapshot($fields, config('app.locale'));
    }
}
