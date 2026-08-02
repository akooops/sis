<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormWebhookData;
use App\Data\Form\StoreFormWebhookData;
use App\Data\Form\UpdateFormWebhookData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Form;
use App\Models\FormWebhook;
use App\Services\Forms\WebhookConfig;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * A form's outbound deliveries.
 *
 * Not a pivot — a webhook is a record with its own fields — so the index is the
 * flat list scoped by `filter[form_id]`, while `store` hangs off the form it
 * belongs to: the owner comes from the URL, never from the body.
 *
 * `last_delivered_at` is written by the job through saveQuietly and is read-only
 * here. It is the only delivery column: what a response actually was belongs in
 * the `integrations` log, not in a field the admin can sort by and misread.
 */
class FormWebhooksController extends ApiController
{
    public function index(): JsonResponse
    {
        $webhooks = QueryBuilder::for(FormWebhook::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('form_id'),
                AllowedFilter::exact('is_enabled'),
                AllowedFilter::exact('auth_type'),
                AllowedFilter::exact('method'),
                $this->search(['name', 'url']),
            ])
            ->allowedSorts([
                'id',
                'name',
                'url',
                'method',
                'is_enabled',
                'last_delivered_at',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(
            FormWebhookData::collect($webhooks, PaginatedDataCollection::class),
            'Form webhooks retrieved successfully',
        );
    }

    public function store(StoreFormWebhookData $data, Form $form): JsonResponse
    {
        $webhook = FormWebhook::create([
            'form_id' => $form->id,
            'name' => $data->name,
            'url' => $data->url,
            'method' => $data->method,
            'is_enabled' => $data->is_enabled,
            'auth_type' => $data->auth_type,
            'auth_config' => WebhookConfig::foldAuth(null, $data->auth_type, $data->auth),
        ]);

        return $this->respond(FormWebhookData::from($webhook), 'Webhook created successfully', 201);
    }

    public function update(UpdateFormWebhookData $data, FormWebhook $formWebhook): JsonResponse
    {
        // The config is folded BEFORE the type is written: the "keep the stored
        // secret" rule compares against the type the row currently has, and
        // updating first would make every change look like a type it never had.
        $config = WebhookConfig::foldAuth($formWebhook, $data->auth_type, $data->auth);

        $formWebhook->update([
            'name' => $data->name,
            'url' => $data->url,
            'method' => $data->method,
            'is_enabled' => $data->is_enabled,
            'auth_type' => $data->auth_type,
            'auth_config' => $config,
        ]);

        return $this->respond(FormWebhookData::from($formWebhook->fresh()), 'Webhook updated successfully');
    }

    public function destroy(FormWebhook $formWebhook): JsonResponse
    {
        $formWebhook->delete();

        return $this->respond(null, 'Webhook deleted successfully');
    }
}
