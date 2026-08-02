<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormSubmissionData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Language;
use App\Models\Media;
use App\Services\Forms\CsvSubmissionWriter;
use App\States\Media\Clean;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Submissions are READ and EXPORTED here — never created, edited or DELETED.
 * The public submit pipeline (Web\FormsController) is the only writer, and an
 * answer an admin could rewrite would stop being evidence of what was sent.
 *
 * There is deliberately no destroy(): a submission is the record the form
 * exists to collect, so nothing in the admin removes one. The only way a row
 * leaves is the retention sweep — FormSubmission is Prunable, which drops
 * abandoned/started rows and rows past forms.submissions.prune_after_days, and
 * that is a retention policy, not a delete button.
 */
class FormSubmissionsController extends ApiController
{
    public function index(): JsonResponse
    {
        $submissions = QueryBuilder::for(FormSubmission::class)
            // withCount + media on the FORM, not per row: FormData falls back to
            // pages()->count() and getFirstMediaUrl() when they are missing, which
            // would be three queries per submission.
            ->with(['form' => fn ($query) => $query->withCount(['pages', 'fields']), 'form.media'])
            ->allowedFilters($this->filters())
            ->allowedSorts(['id', 'status', 'submitted_at', 'spam_score', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(
            FormSubmissionData::collect($submissions, PaginatedDataCollection::class),
            'Form submissions retrieved successfully',
        );
    }

    public function show(FormSubmission $formSubmission): JsonResponse
    {
        $formSubmission->load([
            'media',
            'form' => fn ($query) => $query->withCount(['pages', 'fields']),
            'form.media',
        ]);

        return $this->respond(FormSubmissionData::from($formSubmission), 'Form submission retrieved successfully');
    }

    /**
     * The CSV, streamed under the same filters the list is showing.
     *
     * getEloquentBuilder() is the ONLY way to hand the filtered query on: a
     * QueryBuilder is not an Eloquent builder, and ->toBase()->getModel()
     * ->newQuery() both throws and would throw the filters away.
     */
    public function export(Form $form, CsvSubmissionWriter $writer): StreamedResponse
    {
        $query = QueryBuilder::for($form->submissions())
            ->allowedFilters($this->filters())
            ->allowedSorts(['id', 'status', 'submitted_at', 'spam_score', 'created_at'])
            ->defaultSort('-submitted_at')
            ->getEloquentBuilder();

        // Counted and audited before a byte is sent — see CsvSubmissionWriter::log().
        $writer->log($form, (clone $query)->count());

        return $writer->stream($form, $query, $this->locale());
    }

    /**
     * Download one answer file.
     *
     * Answer uploads live on the PRIVATE disk and have no URL, so this route is
     * the only way to them: signature-gated (the link expires) AND permission-
     * gated (an expired-signature check is not authorisation). The media must
     * still belong to THIS submission's answers collection — without that check
     * any id would do, and the submission binding would be decoration.
     *
     * Reached by a link click from the admin page, which is what makes the
     * session stateful; a URL pasted into a bare tab sends no Referer and is
     * rejected by Sanctum before it gets here.
     */
    public function file(FormSubmission $formSubmission, Media $media): StreamedResponse
    {
        abort_unless(
            $media->model_type === $formSubmission->getMorphClass()
                && $media->model_id === $formSubmission->getKey()
                && $media->collection_name === FormSubmission::ANSWERS_COLLECTION,
            404,
        );

        // A quarantined or infected upload is never served.
        abort_unless($media->state instanceof Clean, 404);

        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($media->path), 404);

        // The stored name is a ULID; the visitor's filename is what downloads.
        return $disk->download($media->path, $media->name);
    }

    /**
     * Shared by the list and the export, so a CSV can never contain rows the
     * list was not showing.
     *
     * @return array<int, AllowedFilter>
     */
    protected function filters(): array
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::exact('form_id'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('country_code'),
            AllowedFilter::exact('device_type'),
            AllowedFilter::exact('is_honeypot_triggered'),
            // The ULID is the only identifier a submission has, and it is the
            // string the visitor was shown — so it is the whole of the search.
            $this->search(['id']),
            $this->date('submitted_from', '>=', 'startOfDay', 'submitted_at'),
            $this->date('submitted_to', '<=', 'endOfDay', 'submitted_at'),
        ];
    }

    /** The locale labels and answers are rendered in. Enabled ones only. */
    protected function locale(): string
    {
        $locale = (string) request()->query('locale', '');

        return in_array($locale, Language::enabledCodes(), true) ? $locale : Language::defaultCode();
    }
}
