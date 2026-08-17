<?php

namespace App\Services\Jobs;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The job applications CSV.
 *
 * FIXED COLUMNS, AND THAT IS THE DIFFERENCE FROM CsvSubmissionWriter. A
 * submissions export needs a {form} because its columns ARE that form's fields;
 * an application has typed columns that mean the same thing for every posting, so
 * a cross-posting export is meaningful and the route takes no parameter.
 *
 * Like its sibling: a UTF-8 BOM because Excel will not otherwise read one, which
 * makes EXCEL THE CONSUMER and is why every cell is escaped against formula
 * injection before it is written.
 */
class CsvApplicationWriter
{
    public const HEADERS = [
        'ID',
        'Applicant',
        'Email',
        'Phone',
        'Nationality',
        'Posting',
        'Status',
        'Match score',
        'Applied at',
    ];

    /** Cells opening with one of these are executed by Excel as a formula. */
    protected const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Stream the export.
     *
     * $query is the filtered ELOQUENT builder — QueryBuilder::getEloquentBuilder()
     * hands it over with every filter still applied, so a CSV can never contain
     * rows the list was not showing. Nothing here re-derives the filters, and
     * nothing loads the whole result set into memory.
     */
    public function stream(EloquentBuilder $query): StreamedResponse
    {
        $chunk = max(100, (int) config('forms.submissions.export_chunk', 500));

        $callback = function () use ($query, $chunk) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, static::HEADERS);

            $written = 0;

            /*
             * reorder() FIRST. lazyById() only strips an existing order on the id
             * column, so a leading `applied_at desc` would survive into the keyset
             * walk and silently skip or repeat rows between chunks.
             *
             * candidateMatches is eager-loaded so match() reads the collection
             * rather than firing a query per row — the same guard the DTO relies on.
             */
            foreach ($query->with(['candidate.country', 'jobOffer', 'candidateMatches'])->reorder()->lazyById($chunk) as $application) {
                fputcsv($handle, $this->row($application));

                if (++$written % $chunk === 0) {
                    flush();
                }
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $this->filename(), [
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
     * A HAND-ROLLED activity(), which observers otherwise own — the same
     * exception CsvSubmissionWriter::log() and UploadService::log() carry, for the
     * same reason: reading data out changes no column, so there is no model event
     * to observe and no diff to record. Without this, the one action that removes
     * personal data from the building leaves no trace.
     *
     * No subject: an export spans postings, so there is no single record it was
     * performed on. Written BEFORE the response streams, since a throw once output
     * has started would land mid-CSV.
     */
    public function log(int $count): void
    {
        activity('job-applications')
            ->event('exported')
            ->withProperties([
                'count' => $count,
                'filter' => request()->query('filter', []),
                'meta' => ['count' => $count],
            ])
            ->log('exported');
    }

    /**
     * @return array<int, string>
     */
    protected function row(JobApplication $application): array
    {
        $candidate = $application->candidate;
        $match = $application->match();

        return array_map([$this, 'safe'], [
            $application->id,
            $candidate?->full_name ?? '',
            $candidate?->email ?? '',
            $candidate?->phone ?? '',
            $candidate?->country?->name ?? '',
            $application->jobOffer?->name ?? '',
            (string) $application->status,
            // BLANK, NOT ZERO, when unscored. A 0 in this column would read as
            // "the model rated them nothing", which is a different claim from
            // "the queue has not reached them".
            $match?->score !== null ? (string) $match->score : '',
            $application->applied_at?->toDateTimeString() ?? '',
        ]);
    }

    protected function safe(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], static::FORMULA_PREFIXES, true)) {
            return "'".$value;
        }

        return $value;
    }

    protected function filename(): string
    {
        return 'job-applications-'.now()->format('Ymd-His').'.csv';
    }
}
