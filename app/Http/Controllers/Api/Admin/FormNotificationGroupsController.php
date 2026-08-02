<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormNotificationGroupData;
use App\Data\Form\StoreFormNotificationGroupData;
use App\Http\Controllers\Api\ApiController;
use App\Models\FormNotificationGroup;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

/**
 * Which groups a form notifies — the same rows, reachable from both ends.
 *
 * The index is FLAT and scoped by whichever parent the caller has
 * (filter[form_id] from the form's page, filter[notification_group_id] from the
 * group's), rather than nested under one of them: a nested URL would have to
 * pick an owner, and this pivot does not have one.
 *
 * There is no update — a link is two foreign keys.
 */
class FormNotificationGroupsController extends ApiController
{
    public function index(): JsonResponse
    {
        $query = QueryBuilder::for(FormNotificationGroup::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('form_id'),
                AllowedFilter::exact('notification_group_id'),
                $this->searchRelationByColumns('group', ['id', 'name', 'code']),
                $this->searchRelationByColumns('form', ['id', 'name', 'slug']),
            ])
            ->allowedIncludes(['form', 'group'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at');

        // allowedIncludes applies a plain with(); re-declaring the SAME relation
        // afterwards replaces that closure with a constrained one, which is what
        // fills in the counts the Data classes read. Only what was asked for —
        // the form's page ships groups, the group's page ships forms, and
        // neither pays for the other.
        $includes = QueryBuilderRequest::fromRequest(request())->includes();

        if ($includes->contains('group')) {
            $query->with(['group' => fn ($group) => $group->with(['types', 'integration'])->withCount(['types', 'users'])]);
        }

        if ($includes->contains('form')) {
            $query->with(['form' => fn ($form) => $form->withCount(['pages', 'fields'])]);
        }

        $links = $query->paginate($this->perPage())->appends(request()->query());

        return $this->respond(FormNotificationGroupData::collect($links, PaginatedDataCollection::class), 'Form notification groups retrieved successfully');
    }

    /**
     * Attach several at once, from whichever side asked.
     *
     * Through the pivot MODEL, never $form->notificationGroups()->attach(): the
     * table carries its own ULID primary key with no database default, so a raw
     * pivot insert fails on it — and only a model write fires the observer that
     * audits this against the form.
     */
    public function store(StoreFormNotificationGroupData $data): JsonResponse
    {
        $links = $data->form_id !== null
            ? collect($data->notification_groups ?? [])->map(fn (string $groupId) => FormNotificationGroup::create([
                'form_id' => $data->form_id,
                'notification_group_id' => $groupId,
            ]))
            : collect($data->forms ?? [])->map(fn (string $formId) => FormNotificationGroup::create([
                'form_id' => $formId,
                'notification_group_id' => $data->notification_group_id,
            ]));

        $links->each->load(['form', 'group']);

        return $this->respond(FormNotificationGroupData::collect($links->all()), 'Notification routing added successfully', 201);
    }

    public function destroy(FormNotificationGroup $formNotificationGroup): JsonResponse
    {
        $formNotificationGroup->delete();

        return $this->respond(null, 'Notification routing removed successfully');
    }
}
