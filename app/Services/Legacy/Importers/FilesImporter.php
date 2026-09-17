<?php

namespace App\Services\Legacy\Importers;

use App\Services\Legacy\LegacyImporter;

/**
 * Every row of the old `files` table, as a free media row here, with the bytes
 * copied across UNDER THE SAME NAME.
 *
 * RUNS FIRST, AND EVERYTHING ELSE DEPENDS ON IT. Content importers do not import
 * their own files; they attach one that this module has already created. That
 * ordering is what lets a file be claimed by whichever module happens to run —
 * and lets the ones nobody claims stay in the library instead of vanishing.
 *
 * The files nobody claims are the point, not the leftovers: editor-inserted
 * images were never attached to anything in either app. They exist only as an
 * `<img src="/storage/uploads/...">` inside a translated `content` column, and
 * because names are preserved (see LegacyFiles) those bodies keep working with
 * no HTML rewriting at all.
 *
 * The old `media` table — a thin label layer over `files`, carrying a translated
 * title and description — has no counterpart here; this app's media library IS
 * the file list. Its labels are reported as dropped rather than folded into
 * `media.name`, which is the original filename and is relied on as such.
 */
class FilesImporter extends LegacyImporter
{
    public function module(): string
    {
        return 'files';
    }

    public function describe(): string
    {
        return 'Uploaded files → media library (bytes copied, names preserved)';
    }

    public function sources(): array
    {
        return ['files'];
    }

    public function run(): void
    {
        if (! $this->c->files->hasRoot()) {
            $this->c->warn(
                'No --files directory given, so no bytes were copied. '
                .'Media rows are only written for files that are actually on disk; '
                .'re-run with --files once the uploads are in place.'
            );

            return;
        }

        $this->each('files', function (object $row) {
            if ($this->c->dryRun) {
                $this->c->created();

                return;
            }

            // Asked BEFORE the import, because import() records the mapping it is
            // about to create — afterwards every row looks like a re-run.
            $known = $this->c->files->alreadyImported($row);

            $media = $this->c->files->import($row);

            if (! $media) {
                $this->c->skipped();

                return;
            }

            $known ? $this->c->skipped() : $this->c->created();
        });

        $stats = $this->c->files->stats();

        $this->c->note(
            "{$stats['copied']} file(s) copied, {$stats['skipped']} already on disk, "
            ."{$stats['reused']} already imported."
        );

        if ($stats['missing'] !== []) {
            $sample = array_slice($stats['missing'], 0, 5);

            $this->c->warn(
                count($stats['missing']).' file(s) named in the legacy database were not in the files '
                .'directory and were skipped, e.g. '.implode(', ', $sample)
                .'. Anything that referenced them will render its placeholder.'
            );
        }

        if ($this->c->db->has('media')) {
            $labels = $this->c->db->table('media')->count();

            if ($labels > 0) {
                $this->c->note(
                    "The legacy `media` table's {$labels} title/description labels were not carried across — "
                    .'this app has no label layer over a file, and `media.name` holds the original filename.'
                );
            }
        }
    }
}
