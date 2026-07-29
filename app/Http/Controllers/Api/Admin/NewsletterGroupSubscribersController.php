<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\NewsletterGroupSubscriber\NewsletterGroupSubscriberData;
use App\Data\NewsletterGroupSubscriber\StoreNewsletterGroupSubscriberData;
use App\Data\NewsletterGroupSubscriber\UpdateNewsletterGroupSubscriberData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NewsletterGroup;
use App\Models\NewsletterGroupSubscriber;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NewsletterGroupSubscribersController extends ApiController
{
    public function index(): JsonResponse
    {
        $subscribers = QueryBuilder::for(NewsletterGroupSubscriber::class)
            ->with('group')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('newsletter_group_id'),
                AllowedFilter::exact('is_active'),
                $this->search(['id', 'name', 'email']),
            ])
            ->allowedSorts(['id', 'name', 'email', 'created_at'])
            // A signup log: the newest address is the interesting one.
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NewsletterGroupSubscriberData::collect($subscribers, PaginatedDataCollection::class), 'Newsletter group subscribers retrieved successfully');
    }

    /** Nested list for one group — the group is the route, so it is not re-loaded. */
    public function forGroup(NewsletterGroup $newsletterGroup): JsonResponse
    {
        $subscribers = QueryBuilder::for(NewsletterGroupSubscriber::query()->where('newsletter_group_id', $newsletterGroup->getKey()))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_active'),
                $this->search(['id', 'name', 'email']),
            ])
            ->allowedSorts(['id', 'name', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NewsletterGroupSubscriberData::collect($subscribers, PaginatedDataCollection::class), 'Newsletter group subscribers retrieved successfully');
    }

    public function show(NewsletterGroupSubscriber $newsletterGroupSubscriber): JsonResponse
    {
        return $this->respond(NewsletterGroupSubscriberData::from($newsletterGroupSubscriber->load('group')), 'Newsletter group subscriber retrieved successfully');
    }

    public function store(StoreNewsletterGroupSubscriberData $data): JsonResponse
    {
        $subscriber = NewsletterGroupSubscriber::create([
            'newsletter_group_id' => $data->newsletter_group_id,
            'email' => $data->email,
            'name' => $data->name,
            'is_active' => $data->is_active,
            // A public signup sends no date: it happened now, by definition.
            'subscribed_at' => $data->subscribed_at ?? now(),
        ]);

        return $this->respond(NewsletterGroupSubscriberData::from($subscriber->load('group')), 'Newsletter group subscriber created successfully', 201);
    }

    public function update(UpdateNewsletterGroupSubscriberData $data, NewsletterGroupSubscriber $newsletterGroupSubscriber): JsonResponse
    {
        $newsletterGroupSubscriber->update([
            'newsletter_group_id' => $data->newsletter_group_id,
            'email' => $data->email,
            'name' => $data->name,
            'is_active' => $data->is_active,
            // When they signed up is history, not a field an edit may blank.
            'subscribed_at' => $data->subscribed_at ?? $newsletterGroupSubscriber->subscribed_at,
        ]);

        return $this->respond(NewsletterGroupSubscriberData::from($newsletterGroupSubscriber->fresh()->load('group')), 'Newsletter group subscriber updated successfully');
    }

    public function destroy(NewsletterGroupSubscriber $newsletterGroupSubscriber): JsonResponse
    {
        $newsletterGroupSubscriber->delete();

        return $this->respond(null, 'Newsletter group subscriber deleted successfully');
    }
}
