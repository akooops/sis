<?php

namespace App\Services\Legacy\Importers;

use App\Models\Grade;
use App\Models\Program;
use App\Models\Stream;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Programmes, and the two tables that hang off them.
 *
 * ONE MODULE, THREE TABLES, because a programme without its streams and grades
 * is not a programme anyone can use — and because both children are resolved
 * through the parent's id map entry, so splitting them would only create an
 * ordering constraint to get wrong.
 *
 * `program_streams` is simply `streams` here (the table was renamed when the
 * remake dropped the redundant prefix), and `programs.has_streams` is gone: this
 * app decides by asking whether the programme HAS any, which cannot fall out of
 * step with the rows the way a cached boolean can.
 */
class ProgramsImporter extends ContentImporter
{
    public function module(): string
    {
        return 'programs';
    }

    public function describe(): string
    {
        return 'Programmes, streams and grades';
    }

    public function sources(): array
    {
        return ['programs'];
    }

    protected function source(): string
    {
        return 'programs';
    }

    protected function target(): string
    {
        return Program::class;
    }

    protected function translated(): array
    {
        return ['title', 'subtitle', 'description', 'content'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Program::THUMBNAIL_COLLECTION;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function hasOrder(): bool
    {
        return true;
    }

    public function run(): void
    {
        parent::run();

        $this->streams();
        $this->grades();
    }

    /** `program_streams` → `streams`, keeping colour and order. */
    protected function streams(): void
    {
        $translations = $this->translationsFor('App\Models\ProgramStream');

        $this->each('program_streams', function (object $row) use ($translations) {
            $programId = $this->c->map->find('programs', $row->program_id);

            if (! $programId) {
                $this->c->warn("Stream [{$row->name}] belongs to a programme that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            $model = $this->model(Stream::class, 'program_streams', (int) $row->id, ['slug' => $row->slug]);

            $model->name = (string) $row->name;
            $model->slug = $this->slug($row->slug ?? $row->name, $row->id);
            $model->color = $row->color ?: Stream::DEFAULT_COLOR;
            $model->order = (int) ($row->order ?? 0);
            $model->program_id = $programId;

            foreach ($this->c->translations->pick($translations, (int) $row->id, ['title', 'description', 'content', 'cta']) as $column => $values) {
                $model->setTranslations($column, array_merge($model->getTranslations($column), $values));
            }

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'program_streams', (int) $row->id);
        });
    }

    /**
     * Grades.
     *
     * NO NATURAL KEY IS POSSIBLE: a grade is only "a name inside a programme",
     * and two programmes legitimately both have a Year 4. The id map is the only
     * thing that can tell a re-run which row it wrote last time, which is the
     * clearest argument for the map existing at all.
     */
    protected function grades(): void
    {
        $translations = $this->translationsFor('App\Models\Grade');

        $this->each('grades', function (object $row) use ($translations) {
            $programId = $this->c->map->find('programs', $row->program_id);

            if (! $programId) {
                $this->c->warn("Grade [{$row->name}] belongs to a programme that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            $model = $this->model(Grade::class, 'grades', (int) $row->id);

            $model->name = (string) $row->name;
            $model->order = (int) ($row->order ?? 0);
            $model->program_id = $programId;

            foreach ($this->c->translations->pick($translations, (int) $row->id, ['title']) as $column => $values) {
                $model->setTranslations($column, array_merge($model->getTranslations($column), $values));
            }

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'grades', (int) $row->id);

            $this->guidelines($row, $model);
        });
    }

    /**
     * A grade's downloadable guidelines.
     *
     * EASY TO MISS, because the old schema gave them no table and no column: a
     * guideline was just a `files` row morphed onto the Grade with `is_main = 0`,
     * and the old Grade model declared no file relation at all — the admin
     * listed them by querying files directly. Nothing in the grades table hints
     * that they exist, so the only way to find them is to ask which models own
     * files. This install has one per grade, all fifteen.
     *
     * Multi-file here, so every file is kept rather than the last one winning.
     */
    protected function guidelines(object $row, Model $model): void
    {
        if ($this->c->dryRun || ! $this->c->db->has('files')) {
            return;
        }

        $files = $this->c->db->table('files')
            ->where('model_type', 'App\Models\Grade')
            ->where('model_id', $row->id)
            ->orderBy('id')
            ->pluck('id');

        foreach ($files as $order => $id) {
            $this->c->files->attach($id, $model, Grade::GUIDELINES_COLLECTION, $order);
        }
    }

    protected function extra(object $row, Model $model): void
    {
        // has_streams is not carried: this app asks the streams table instead.
    }
}
