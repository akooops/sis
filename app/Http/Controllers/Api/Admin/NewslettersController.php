<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Newsletter\NewsletterData;
use App\Data\Newsletter\StoreNewsletterData;
use App\Data\Newsletter\UpdateNewsletterData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Newsletter;
use App\Services\Uploads\UploadService;
use App\States\Newsletter\NewsletterStatus;
use App\States\Newsletter\Sending;
use App\States\Newsletter\Sent;
use App\States\NewsletterPublication\NewsletterPublicationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Broadcasts. There is deliberately NO send-now endpoint: sending is always
 * newsletters:send-scheduled dispatching ShipNewsletter, so "send now" from the
 * UI means status=scheduled with scheduled_at=now and the command picks it up on
 * its next tick. Don't add a direct send here — it would skip the Sending/Sent
 * audit trail and the queue's retries.
 *
 * Two pipelines that never read each other: `publish_status` + `published_at` put
 * the issue in the website archive (newsletters:publish-scheduled), `status` +
 * `scheduled_at` email it. A publish-only issue stays draft on the email side
 * forever, and a send-only issue stays draft on the website side.
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
                AllowedFilter::exact('status'),
                AllowedFilter::exact('publish_status'),
                AllowedFilter::exact('is_published'),
                AllowedFilter::exact('is_sendable'),
                AllowedFilter::exact('integration_id'),
                $this->searchRelationById('newsletter_group_id', 'groups'),
                $this->search(['id', 'name', 'subject']),
            ])
            ->allowedSorts(['id', 'name', 'subject', 'status', 'publish_status', 'published_at', 'scheduled_at', 'sent_at', 'created_at'])
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
        $status = $this->sendingStatus($data->is_sendable, $data->status);
        $publishStatus = $this->publicationStatus($data->is_published, $data->publish_status);

        $newsletter = Newsletter::create([
            'name' => $data->name,
            'is_published' => $data->is_published,
            'is_sendable' => $data->is_sendable,
            'title' => $data->title ? [Language::defaultCode() => $data->title] : [],
            // The column is NOT NULL; a publish-only issue simply has no subject line.
            'subject' => $data->subject,
            'content' => $data->content,
            'integration_id' => $data->integration_id,
            'publish_status' => NewsletterPublicationStatus::resolveStateClass($publishStatus),
            // Publishing is always "now": the admin picks the status, we stamp the moment.
            'published_at' => $publishStatus === 'published' ? now() : $data->published_at,
            'status' => NewsletterStatus::resolveStateClass($status),
            'scheduled_at' => $status === 'scheduled' ? $data->scheduled_at : null,
        ]);

        if ($data->file) {
            UploadService::attach($data->file, $newsletter, Newsletter::FILE_COLLECTION);
        }

        $newsletter->syncGroups($data->group_ids);

        return $this->respond(NewsletterData::from($newsletter->fresh()->load(['groups', 'integration', 'media'])), 'Newsletter created successfully', 201);
    }

    public function update(UpdateNewsletterData $data, Newsletter $newsletter): JsonResponse
    {
        $this->guardInFlight($newsletter, 'edited');

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

        $publishStatus = $this->publicationStatus($data->is_published, $data->publish_status);
        $publishTarget = NewsletterPublicationStatus::resolveStateClass($publishStatus);

        if (! $newsletter->publish_status instanceof $publishTarget) {
            try {
                $newsletter->publish_status->transitionTo($publishTarget);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'publish_status' => "A {$newsletter->publish_status->getValue()} newsletter cannot become {$publishStatus}.",
                ]);
            }
        }

        // Keep the first publication date on a re-publish; a hidden issue that goes
        // back up still says when it originally went live.
        $newsletter->published_at = $publishStatus === 'published'
            ? ($newsletter->published_at ?? now())
            : $data->published_at;

        $status = $this->sendingStatus($data->is_sendable, $data->status);
        $target = NewsletterStatus::resolveStateClass($status);

        if (! $newsletter->status instanceof $target) {
            try {
                $newsletter->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$newsletter->status->getValue()} newsletter cannot become {$status}.",
                ]);
            }
        }

        $newsletter->scheduled_at = $status === 'scheduled' ? $data->scheduled_at : null;
        $newsletter->save();

        if (! $data->file instanceof Optional && $data->file) {
            UploadService::attach($data->file, $newsletter, Newsletter::FILE_COLLECTION);
        }

        $newsletter->syncGroups($data->group_ids);

        return $this->respond(NewsletterData::from($newsletter->fresh()->load(['groups', 'integration', 'media'])), 'Newsletter updated successfully');
    }

    public function destroy(Newsletter $newsletter): JsonResponse
    {
        $this->guardInFlight($newsletter, 'deleted');

        $newsletter->delete();

        return $this->respond(null, 'Newsletter deleted successfully');
    }

    /**
     * The status pipeline governs sending only, so an issue that is not sendable
     * can never be scheduled — it stays draft forever and the command skips it.
     */
    protected function sendingStatus(bool $sendable, ?string $status): string
    {
        return $sendable ? ($status ?? 'draft') : 'draft';
    }

    /**
     * The publish_status pipeline governs the website archive alone, so an issue
     * that is not published can never be scheduled or live.
     *
     * Switching the website off on a LIVE issue is a withdrawal, so it lands on
     * hidden rather than draft: Published -> Draft is barred, and forcing it would
     * 422 on a field the form has already hidden — a save that silently does
     * nothing. Hidden also keeps published_at, so re-publishing remembers.
     */
    protected function publicationStatus(bool $published, ?string $status, ?Newsletter $newsletter = null): string
    {
        if ($published) {
            return $status ?? 'draft';
        }

        return $newsletter?->publish_status instanceof PublicationPublished ? 'hidden' : 'draft';
    }

    /**
     * Once the jobs are out the content is already on its way to real inboxes —
     * editing or deleting it would only rewrite our copy of history.
     */
    protected function guardInFlight(Newsletter $newsletter, string $action): void
    {
        if ($newsletter->status instanceof Sending || $newsletter->status instanceof Sent) {
            throw ValidationException::withMessages([
                'status' => "This newsletter is {$newsletter->status->getValue()} and can no longer be {$action}.",
            ]);
        }
    }
}
