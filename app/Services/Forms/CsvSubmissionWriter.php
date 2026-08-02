<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\Forms\FieldTypes\FileType;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The submissions CSV.
 *
 * ONE writer, one format. No `?format=` switch and no exporter interface: a
 * second format is a second class, and the abstraction that would let them
 * share this one costs more than the duplication would.
 *
 * COLUMNS FOLLOW THE FORM'S CURRENT FIELDS, not each row's snapshot. A CSV has
 * exactly one header row, so a per-row shape is not expressible — an answer to
 * a field that has since been deleted is simply not in the file. That is the
 * trade, and the export button says so.
 *
 * The file opens with a UTF-8 BOM because Excel will not otherwise read one, so
 * EXCEL IS THE CONSUMER, which is why every cell is escaped against formula
 * injection before it is written.
 */
class CsvSubmissionWriter
{
    /** Fixed columns, before the one-per-field answer columns. */
    public const META_HEADERS = [
        'ID',
        'Status',
        'Submitted at',
        'Duration (seconds)',
        'Country',
        'Device',
        'Browser',
        'OS',
        'Spam score',
    ];

    /** Cells opening with one of these are executed by Excel as a formula. */
    protected const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Stream the export.
     *
     * $query is the filtered ELOQUENT builder — QueryBuilder::getEloquentBuilder()
     * hands it over with every filter still applied. Nothing here re-derives the
     * filters, and nothing loads the whole result set into memory.
     */
    public function stream(Form $form, EloquentBuilder $query, string $locale): StreamedResponse
    {
        $fields = $this->fields($form);
        $chunk = max(100, (int) config('forms.submissions.export_chunk', 500));

        $callback = function () use ($fields, $query, $locale, $chunk) {
            $handle = fopen('php://output', 'w');

            // Without the BOM Excel reads UTF-8 as the system codepage and every
            // non-ASCII answer arrives as mojibake.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_merge(
                static::META_HEADERS,
                $fields->map(fn (FormField $field) => $this->safe($this->header($field, $locale)))->all(),
            ));

            $written = 0;

            // reorder() FIRST. lazyById() only strips an existing order on the id
            // column, so a leading `submitted_at desc` would survive into the
            // keyset walk and silently skip or repeat rows between chunks.
            foreach ($query->with('media')->reorder()->lazyById($chunk) as $submission) {
                fputcsv($handle, $this->row($fields, $submission, $locale));

                if (++$written % $chunk === 0) {
                    flush();
                }
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $this->filename($form), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            // nginx buffers a proxied response by default, which would hold the
            // whole file before sending a byte and defeat the streaming.
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * The export's audit row.
     *
     * A HAND-ROLLED activity(), which observers otherwise own exclusively — the
     * second and last such exception in the app, alongside UploadService::log().
     * Reading data out of the system changes no column, so there is no model
     * event to observe and no diff to record; without this, the one action that
     * removes personal data from the building leaves no trace at all.
     *
     * Written BEFORE the response streams: once output has started a throw here
     * would land in the middle of a half-sent CSV.
     */
    public function log(Form $form, int $count): void
    {
        activity('form-submissions')
            ->performedOn($form)
            ->event('exported')
            ->withProperties([
                'count' => $count,
                'filter' => request()->query('filter', []),
                'meta' => ['name' => $form->name, 'count' => $count],
            ])
            ->log('exported');
    }

    /**
     * The form's current answer-capturing fields, in reading order.
     *
     * Sorted in PHP rather than by a join: the relation already orders by
     * `order`, and both form_fields and form_pages have a column of that name,
     * so joining makes the existing ORDER BY ambiguous and MySQL refuses it.
     *
     * @return Collection<int, FormField>
     */
    protected function fields(Form $form): Collection
    {
        return $form->fields()
            ->capturing()
            ->with('page')
            ->get()
            ->sortBy([['page.order', 'asc'], ['order', 'asc']])
            ->values();
    }

    /** The column header: the field's label in the export locale, else its key. */
    protected function header(FormField $field, string $locale): string
    {
        $label = trim((string) $field->getTranslation('label', $locale));

        return $label !== '' ? $label : $field->key;
    }

    /**
     * @param  Collection<int, FormField>  $fields
     * @return array<int, string>
     */
    protected function row(Collection $fields, FormSubmission $submission, string $locale): array
    {
        $row = [
            // The ULID is the submission's only identifier — the same string the
            // visitor was shown on the thank-you page.
            $submission->id,
            $submission->status,
            $submission->submitted_at?->toDateTimeString(),
            $submission->duration_seconds,
            $submission->country_code,
            $submission->device_type,
            $submission->browser,
            $submission->os,
            $submission->spam_score,
        ];

        foreach ($fields as $field) {
            $row[] = $this->cell($field, $submission, $locale);
        }

        return array_map(fn ($value) => $this->safe($value), $row);
    }

    /** One answer, rendered by its own element. */
    protected function cell(FormField $field, FormSubmission $submission, string $locale): string
    {
        $value = $submission->answer($field->key);
        $element = $field->element();

        // A file answer stores media ids. Swapped for the uploaded filenames
        // before display() joins them — a column of ULIDs tells the admin
        // nothing, and the media are already loaded with the chunk.
        if ($element instanceof FileType) {
            $value = $this->fileNames($submission, $value);
        }

        // A type dropped from config must degrade, not 500 the export — the same
        // guard FormField::element() exists for.
        return $element ? $element->display($field, $value, $locale) : $this->flatten($value);
    }

    /** Media ids replaced by their original filenames, id kept when unresolved. */
    protected function fileNames(FormSubmission $submission, mixed $value): mixed
    {
        $names = $submission->media->pluck('name', 'id');

        $resolve = fn ($id) => is_string($id) ? ($names[$id] ?? $id) : $id;

        return is_array($value) ? array_map($resolve, $value) : $resolve($value);
    }

    /** Last-resort rendering for an element whose class is gone. */
    protected function flatten(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return implode(', ', array_map(
                fn ($item) => is_scalar($item) ? (string) $item : (string) json_encode($item),
                $value,
            ));
        }

        return (string) $value;
    }

    /**
     * Neutralise CSV formula injection.
     *
     * A visitor can type `=HYPERLINK("http://evil","click")` into any text field,
     * and Excel will happily run it when the admin opens the export. Leading
     * apostrophe forces the cell to text; Excel does not display it.
     */
    protected function safe(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], static::FORMULA_PREFIXES, true)) {
            return "'".$value;
        }

        return $value;
    }

    protected function filename(Form $form): string
    {
        $name = Str::slug($form->name) ?: 'form';

        return $name.'-submissions-'.now()->format('Ymd-His').'.csv';
    }
}
