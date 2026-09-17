<?php

namespace App\Services\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * The shape nearly every content table in both apps shares: an internal `name`,
 * a `slug`, a publish `status`, a handful of translated columns and one
 * thumbnail.
 *
 * Nine legacy tables answer to exactly this description, so the traversal, the
 * status guard, the translation pivot, the thumbnail attach and the id mapping
 * are written ONCE and each subclass declares only what is true of it. A
 * subclass that needs more overrides extra(), which runs with the row and the
 * model in hand before the save.
 *
 * It is a base class rather than a config array on purpose: an importer with a
 * genuine difference (a programme's streams, a facility's pivots) should be able
 * to write plain code for it, not smuggle a special case through a schema.
 */
abstract class ContentImporter extends LegacyImporter
{
    /** The legacy table. */
    abstract protected function source(): string;

    /** The model to write. */
    abstract protected function target(): string;

    /**
     * Legacy translated field => this app's column.
     *
     * A plain list means the names are the same on both sides, which they are
     * for title/description/content. Anything the old app translated that this
     * app has no column for is simply absent, and config('legacy.dropped')
     * records why.
     *
     * @return array<int|string, string>
     */
    protected function translated(): array
    {
        return ['title', 'description', 'content'];
    }

    /** The legacy model class rows were stored against in `translations`. */
    protected function legacyClass(): string
    {
        return 'App\\Models\\'.class_basename($this->target());
    }

    /** The collection the legacy `file` relation becomes, or null for none. */
    protected function thumbnailCollection(): ?string
    {
        return 'thumbnail';
    }

    /**
     * Whether the legacy model's own file() relation filtered on is_main.
     *
     * True for the models that also had a gallery, where is_main = 0 carries a
     * meaning of its own. See attachThumbnail for why that distinction matters.
     */
    protected function thumbnailIsMainOnly(): bool
    {
        return false;
    }

    /** Whether the legacy table carries a status column. */
    protected function hasStatus(): bool
    {
        return true;
    }

    /** Whether this app's table carries a slug. */
    protected function hasSlug(): bool
    {
        return true;
    }

    /** Whether this app's table carries an order column. */
    protected function hasOrder(): bool
    {
        return false;
    }

    public function sources(): array
    {
        return [$this->source()];
    }

    public function dependsOn(): array
    {
        return ['files'];
    }

    public function run(): void
    {
        $table = $this->source();
        $class = $this->target();
        $translations = $this->translationsFor($this->legacyClass());

        $this->reportDropped($table);

        $this->each($table, function (object $row) use ($table, $class, $translations) {
            $model = $this->model($class, $table, (int) $row->id, $this->naturalKey($row));

            $model->name = (string) ($row->name ?? '');

            if ($this->hasSlug()) {
                $model->slug = $this->slug($row->slug ?? $row->name ?? null, $row->id);
            }

            if ($this->hasStatus() && isset($row->status)) {
                $status = $this->status($row->status);
                $model->status = $status;
                $model->published_at = $model->published_at ?? $this->publishedAt($row, $status);
            }

            if ($this->hasOrder()) {
                $model->order = (int) ($row->order ?? 0);
            }

            /*
             * MERGED, NOT ASSIGNED. A locale this app has enabled that the legacy
             * row never had must not wipe what an admin has already written here
             * — the same reason every update path in this app goes through
             * mergeTranslations() rather than a plain assignment.
             */
            foreach ($this->c->translations->pick($translations, (int) $row->id, $this->translated()) as $column => $values) {
                $model->setTranslations($column, array_merge($model->getTranslations($column), $values));
            }

            $this->extra($row, $model);

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, $table, (int) $row->id);

            $this->attachThumbnail($row, $model);

            if (! $this->c->dryRun) {
                $this->after($row, $model);
            }
        }, fn ($query) => $this->filter($query));
    }

    /**
     * Give the model its legacy `file`.
     *
     * The old `file()` relation was a morphOne keyed on the OWNER, so the file
     * row is found by asking the legacy files table who owned it rather than by
     * reading a column off the row — the old schema had no file_id anywhere
     * except on facilities.
     */
    protected function attachThumbnail(object $row, Model $model): void
    {
        $collection = $this->thumbnailCollection();

        if ($collection === null || ! $this->c->db->has('files')) {
            return;
        }

        $file = $this->c->db->table('files')
            ->where('model_type', $this->legacyClass())
            ->where('model_id', $row->id)
            ->where('is_main', 1)
            ->orderBy('id')
            ->first(['id']);

        /*
         * Fall back to any file the row owned — but ONLY where the legacy model
         * did not itself filter on is_main.
         *
         * Article, Page, Program and Achievement declared file() as a bare
         * morphOne, so their single thumbnail was whatever was uploaded and may
         * well carry is_main = 0; a strict lookup would bring those modules
         * across with no images at all.
         *
         * Album and Event are the opposite case and must NOT fall back: for them
         * is_main = 0 means "gallery", so a fallback would promote a gallery
         * photo to thumbnail and attachGallery would then move that same file
         * back, leaving the row with no thumbnail and one fewer picture.
         */
        if (! $this->thumbnailIsMainOnly()) {
            $file ??= $this->c->db->table('files')
                ->where('model_type', $this->legacyClass())
                ->where('model_id', $row->id)
                ->orderBy('id')
                ->first(['id']);
        }

        if ($file && ! $this->c->dryRun) {
            $this->c->files->attach($file->id, $model, $collection);
        }
    }

    /**
     * A fallback lookup for a row the id map has never seen — used when someone
     * has already hand-created the equivalent row, so the import adopts it
     * instead of inserting a duplicate slug and failing the unique index.
     */
    protected function naturalKey(object $row): ?array
    {
        return $this->hasSlug() && ! empty($row->slug) ? ['slug' => $row->slug] : null;
    }

    /** Narrow which legacy rows are imported. */
    protected function filter($query): void {}

    /** Columns beyond the shared shape. Runs before the save. */
    protected function extra(object $row, Model $model): void {}

    /**
     * Work that needs the row to exist — a gallery, a pivot, children.
     *
     * Runs after the save and the thumbnail, and not at all on a dry run, since
     * everything it can do is a write.
     */
    protected function after(object $row, Model $model): void {}

    /**
     * Attach the legacy `files()` gallery — every file the row owned that is not
     * its thumbnail — to a multi-file collection, in the old app's own order.
     */
    protected function attachGallery(object $row, Model $model, string $collection): void
    {
        if (! $this->c->db->has('files')) {
            return;
        }

        $files = $this->c->db->table('files')
            ->where('model_type', $this->legacyClass())
            ->where('model_id', $row->id)
            ->where('is_main', 0)
            ->orderBy('id')
            ->pluck('id');

        foreach ($files as $order => $id) {
            $this->c->files->attach($id, $model, $collection, $order);
        }
    }
}
