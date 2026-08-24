<?php

namespace App\Services\Facilities;

use App\Models\FacilityReservation;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The venue bookings CSV — the list the desk works from.
 *
 * ONE ROW PER BOOKING, with the students flattened into a single cell, for the
 * same reason GroupType::display() flattens a repeatable group: exploding them
 * into student_1_name, student_2_name and so on makes the column count depend on
 * the largest party in the export, so two exports taken a week apart no longer
 * line up in a spreadsheet.
 *
 * Like its siblings: a UTF-8 BOM because Excel will not otherwise read one, which
 * makes EXCEL THE CONSUMER and is why every cell is escaped against formula
 * injection before it is written.
 */
class CsvReservationWriter
{
    public const HEADERS = [
        'ID',
        'Booked by',
        'Email',
        'Phone',
        'Venue',
        'Starts at',
        'Ends at',
        'Status',
        'Booked at',
        'Note',
    ];

    /** Cells opening with one of these are executed by Excel as a formula. */
    protected const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Stream the export.
     *
     * $query is the filtered ELOQUENT builder — QueryBuilder::getEloquentBuilder()
     * hands it over with every filter still applied, so a CSV can never contain
     * rows the list was not showing.
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
             * column, so a leading `booked_at desc` would survive into the keyset
             * walk and silently skip or repeat rows between chunks.
             */
            foreach ($query->with(['visitor', 'facility', 'slot'])->reorder()->lazyById($chunk) as $reservation) {
                fputcsv($handle, $this->row($reservation));

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
     * A HAND-ROLLED activity(), which observers otherwise own — the same exception
     * CsvApplicationWriter::log() carries, for the same reason: reading data out
     * changes no column, so there is no model event to observe and no diff to
     * record. Without this, the one action that removes personal data from the
     * building leaves no trace.
     *
     * Written BEFORE the response streams, since a throw once output has started
     * would land mid-CSV.
     */
    public function log(int $count): void
    {
        activity('facility-reservations')
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
    protected function row(FacilityReservation $reservation): array
    {
        $visitor = $reservation->visitor;

        return array_map([$this, 'safe'], [
            $reservation->id,
            $visitor?->full_name ?? '',
            $visitor?->email ?? '',
            $visitor?->phone ?? '',
            $reservation->facility?->name ?? '',
            $reservation->slot?->starts_at?->toDateTimeString() ?? '',
            $reservation->slot?->ends_at?->toDateTimeString() ?? '',
            (string) $reservation->status,
            $reservation->booked_at?->toDateTimeString() ?? '',
            $reservation->note ?? '',
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
        return 'facility-reservations-'.now()->format('Ymd-His').'.csv';
    }
}
