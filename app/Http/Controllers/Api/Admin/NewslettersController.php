<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Newsletter\NewsletterData;
use App\Data\Newsletter\StoreNewsletterData;
use App\Data\Newsletter\UpdateNewsletterData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Newsletter;
use App\Services\Uploads\UploadService;
use App\States\NewsletterPublication\NewsletterPublicationStatus;
use App\States\NewsletterPublication\Published;
use App\States\NewsletterSend\NewsletterSendStatus;
use App\States\NewsletterSend\Sent;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Two sides that never read each other: published_status + published_at put the
 * issue in the website archive (newsletters:publish-scheduled), sent_status +
 * sent_at email it (newsletters:send-scheduled). A publish-only issue stays draft
 * on the email side forever, and a send-only issue stays draft on the website side.
 *
 * PICKING THE TERMINAL STATE MEANS "NOW", NEVER A DIRECT WRITE: choosing published
 * stores scheduled + published_at = now, choosing sent stores scheduled + sent_at =
 * now, and the commands do the work on their next tick. That is what keeps all
 * sending inside a command dispatching jobs — a direct send here would bypass the
 * queue, its retries and the audit row.
 */
class NewslettersController extends ApiController
{
    public function index(): JsonResponse
    {
        $newsletters = QueryBuilder::for(Newsletter::class)
            // The DTO reads the file off the media relation on every row.
            ->with(['groups', 'integration', 'media'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('published_status'),
                AllowedFilter::exact('sent_status'),
                AllowedFilter::exact('is_published'),
                AllowedFilter::exact('is_sendable'),
                AllowedFilter::exact('integration_id'),
                $this->searchRelationById('newsletter_group_id', 'groups'),
                $this->search(['id', 'name', 'subject']),
            ])
            ->allowedSorts(['id', 'name', 'subject', 'published_status', 'published_at', 'sent_status', 'sent_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NewsletterData::collect($newsletters, PaginatedDataCollection::class), 'Newsletters retrieved successfully');
    }

    public function show(Newsletter $newsletter): JsonResponse
    {
        return $this->respond(NewsletterData::from($newsletter->load(['groups', 'integration', 'media'])), 'Newsletter retrieved successfully');
    }

    public function store(StoreNewsletterData $data): JsonResponse
    {
        [$publishedStatus, $publishedAt] = $this->publicationState($data->is_published, $data->published_status, $data->published_at);
        [$sentStatus, $sentAt] = $this->sendState($data->is_sendable, $data->sent_status, $data->sent_at);

        $newsletter = Newsletter::create([
            'name' => $data->name,
            'is_published' => $data->is_published,
            'is_sendable' => $data->is_sendable,
            'title' => $data->title ? [Language::defaultCode() => $data->title] : [],
            // The column is nullable; a publish-only issue simply has no subject line.
            'subject' => $data->subject,
            'content' => $data->content,
            'integration_id' => $data->integration_id,
            'published_status' => NewsletterPublicationStatus::resolveStateClass($publishedStatus),
            'published_at' => $publishedAt,
            'sent_status' => NewsletterSendStatus::resolveStateClass($sentStatus),
            'sent_at' => $sentAt,
        ]);

        if ($data->file) {
            UploadService::attach($data->file, $newsletter, Newsletter::FILE_COLLECTION);
        }

        $newsletter->syncGroups($data->group_ids);

        return $this->respond(NewsletterData::from($newsletter->fresh()->load(['groups', 'integration', 'media'])), 'Newsletter created successfully', 201);
    }

    public function update(UpdateNewsletterData $data, Newsletter $newsletter): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $newsletter->update($newsletter->mergeTranslations([
            'name' => $data->name,
            'is_published' => $data->is_published,
            'is_sendable' => $data->is_sendable,
            'title' => $data->title,
            'subject' => $data->subject,
            'content' => $data->content,
            'integration_id' => $data->integration_id,
        ]));

        [$publishedStatus, $publishedAt] = $this->publicationState($data->is_published, $data->published_status, $data->published_at, $newsletter);
        $publishTarget = NewsletterPublicationStatus::resolveStateClass($publishedStatus);

        if (! $newsletter->published_status instanceof $publishTarget) {
            try {
                $newsletter->published_status->transitionTo($publishTarget);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'published_status' => "A {$newsletter->published_status->getValue()} newsletter cannot become {$publishedStatus}.",
                ]);
            }
        }

        $newsletter->published_at = $publishedAt;

        [$sentStatus, $sentAt] = $this->sendState($data->is_sendable, $data->sent_status, $data->sent_at, $newsletter);
        $sendTarget = NewsletterSendStatus::resolveStateClass($sentStatus);

        if (! $newsletter->sent_status instanceof $sendTarget) {
            try {
                $newsletter->sent_status->transitionTo($sendTarget);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'sent_status' => "A {$newsletter->sent_status->getValue()} newsletter cannot become {$sentStatus}.",
                ]);
            }
        }

        $newsletter->sent_at = $sentAt;
        $newsletter->save();

        if (! $data->file instanceof Optional && $data->file) {
            UploadService::attach($data->file, $newsletter, Newsletter::FILE_COLLECTION);
        }

        $newsletter->syncGroups($data->group_ids);

        return $this->respond(NewsletterData::from($newsletter->fresh()->load(['groups', 'integration', 'media'])), 'Newsletter updated successfully');
    }

    public function destroy(Newsletter $newsletter): JsonResponse
    {
        $newsletter->delete();

        return $this->respond(null, 'Newsletter deleted successfully');
    }

    /**
     * The website side: [status, date].
     *
     * Off means draft with no date — the command skips it. Switching it off on a
     * LIVE issue is a withdrawal, so it lands on hidden rather than draft:
     * Published -> Draft is barred, and forcing it would 422 on a field the form
     * has already hidden, a save that silently does nothing. Hidden keeps
     * published_at, so re-publishing remembers when it first went live.
     *
     * @return array{0: string, 1: mixed}
     */
    protected function publicationState(bool $on, ?string $status, ?string $date, ?Newsletter $existing = null): array
    {
        if (! $on) {
            return $existing?->published_status instanceof Published
                ? ['hidden', $existing->published_at]
                : ['draft', null];
        }

        // Picking published means "put it up now": schedule it for this instant and
        // let newsletters:publish-scheduled make it live. Already live is a re-save,
        // not a re-publish — stay put and keep the original date.
        if ($status === 'published') {
            return $existing?->published_status instanceof Published
                ? ['published', $existing->published_at]
                : ['scheduled', now()];
        }

        return [$status ?? 'draft', $date];
    }

    /**
     * The email side: [status, date]. Reads exactly like publicationState.
     *
     * Off means draft with no date. Switching it off on an issue that was already
     * SENT leaves it sent and keeps sent_at: Sent -> Draft is barred, and the mail
     * genuinely went out, so pretending otherwise would lose history.
     *
     * @return array{0: string, 1: mixed}
     */
    protected function sendState(bool $on, ?string $status, ?string $date, ?Newsletter $existing = null): array
    {
        if (! $on) {
            return $existing?->sent_status instanceof Sent
                ? ['sent', $existing->sent_at]
                : ['draft', null];
        }

        // Picking sent means "send it now": schedule it for this instant and let
        // newsletters:send-scheduled dispatch the jobs. Already sent is a re-save,
        // not a re-send — editing the subject of a sent issue must not mail it out
        // again. Re-sending is an explicit move back to scheduled, which the UI
        // confirms first.
        if ($status === 'sent') {
            return $existing?->sent_status instanceof Sent
                ? ['sent', $existing->sent_at]
                : ['scheduled', now()];
        }

        return [$status ?? 'draft', $date];
    }
}
