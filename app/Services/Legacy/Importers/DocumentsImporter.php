<?php

namespace App\Services\Legacy\Importers;

use App\Models\Document;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * TWO LEGACY TABLES, ONE DESTINATION: the old `newsletters` AND the old `forms`
 * are both this app's `documents`.
 *
 * ── THE `forms` TRAP ─────────────────────────────────────────────────────────
 *
 * The old `forms` table is NOT a form builder. It is four columns — id, name and
 * timestamps — with a translated title and one attached file, and on this
 * install it holds "School Contract 2026-2027", "Grade 3 Summer Pack 2026",
 * "Timetables Grade 6-12 Girls". They are DOWNLOADS, and the old site's "Forms"
 * page was a list of them.
 *
 * This app's `forms` table is something else entirely: a builder with pages,
 * fields, options, webhooks, spam guards and submissions. Importing those rows
 * into it would produce fifteen empty forms with no fields and no way to submit,
 * and would collide with the four seeded `is_system` forms the site resolves by
 * slug. So they come here instead, where the shape actually matches.
 *
 * The old `newsletters` table is the same shape for the same reason — a legacy
 * "newsletter" was a downloadable PDF, not a mailing. This app's `newsletters`
 * IS a mailing campaign, and nothing in this import touches it.
 *
 * Both tables are therefore read into `documents`, and `config('legacy.morphs')`
 * points both legacy classes at Document so a menu item linking to either still
 * resolves.
 */
class DocumentsImporter extends ContentImporter
{
    public function module(): string
    {
        return 'documents';
    }

    public function describe(): string
    {
        return 'Downloadable forms and newsletters → documents';
    }

    public function sources(): array
    {
        // Either one is enough to have work to do.
        return [];
    }

    public function available(): bool
    {
        return $this->c->db->has('newsletters') || $this->c->db->has('forms');
    }

    protected function source(): string
    {
        return 'newsletters';
    }

    protected function target(): string
    {
        return Document::class;
    }

    protected function legacyClass(): string
    {
        return 'App\Models\Newsletter';
    }

    protected function translated(): array
    {
        return ['title'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Document::FILE_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function hasSlug(): bool
    {
        return false;
    }

    protected function naturalKey(object $row): ?array
    {
        return ['name' => $row->name];
    }

    public function run(): void
    {
        // The newsletters half, through the shared content shape.
        parent::run();

        $this->legacyForms();
    }

    /**
     * The old `forms` table — same three columns, same one file, different name.
     *
     * Written out rather than folded into ContentImporter because that base
     * describes ONE legacy table per importer, and bending it to take two would
     * cost more than the dozen lines it saves.
     */
    protected function legacyForms(): void
    {
        $translations = $this->translationsFor('App\Models\Form');

        $this->each('forms', function (object $row) use ($translations) {
            $model = $this->model(Document::class, 'forms', (int) $row->id, ['name' => $row->name]);

            $model->name = (string) $row->name;

            foreach ($this->c->translations->pick($translations, (int) $row->id, ['title']) as $column => $values) {
                $model->setTranslations($column, array_merge($model->getTranslations($column), $values));
            }

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'forms', (int) $row->id);

            if ($this->c->dryRun) {
                return;
            }

            $file = $this->c->db->table('files')
                ->where('model_type', 'App\Models\Form')
                ->where('model_id', $row->id)
                ->orderByDesc('is_main')
                ->orderBy('id')
                ->first(['id']);

            if ($file) {
                $this->c->files->attach($file->id, $model, Document::FILE_COLLECTION);
            }
        });
    }

    protected function extra(object $row, Model $model): void {}
}
