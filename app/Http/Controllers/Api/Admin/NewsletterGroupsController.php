<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\NewsletterGroup\NewsletterGroupData;
use App\Data\NewsletterGroup\StoreNewsletterGroupData;
use App\Data\NewsletterGroup\UpdateNewsletterGroupData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\NewsletterGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NewsletterGroupsController extends ApiController
{
    public function index(): JsonResponse
    {
        $groups = QueryBuilder::for(NewsletterGroup::class)
            ->withCount('subscribers')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                // How the signup form finds the list to preselect.
                AllowedFilter::exact('is_default'),
                $this->searchTranslations(['id', 'name', 'code'], ['title', 'description'], Language::enabledCodes()),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            // A handful of rows that never change: alphabetical beats newest-first.
            ->defaultSort('name')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NewsletterGroupData::collect($groups, PaginatedDataCollection::class), 'Newsletter groups retrieved successfully');
    }

    public function show(NewsletterGroup $newsletterGroup): JsonResponse
    {
        return $this->respond(NewsletterGroupData::from($newsletterGroup->loadCount('subscribers')), 'Newsletter group retrieved successfully');
    }

    public function store(StoreNewsletterGroupData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $group = NewsletterGroup::create([
            'name' => $data->name,
            'code' => $data->code,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'is_default' => $data->is_default,
        ]);

        return $this->respond(NewsletterGroupData::from($group->loadCount('subscribers')), 'Newsletter group created successfully', 201);
    }

    public function update(UpdateNewsletterGroupData $data, NewsletterGroup $newsletterGroup): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $newsletterGroup->update($newsletterGroup->mergeTranslations([
            'name' => $data->name,
            'code' => $data->code,
            'title' => $data->title,
            'description' => $data->description,
            'is_default' => $data->is_default,
        ]));

        return $this->respond(NewsletterGroupData::from($newsletterGroup->fresh()->loadCount('subscribers')), 'Newsletter group updated successfully');
    }

    /**
     * A list owns its subscribers, so they go with it.
     *
     * The removal is not optional — the FK is restrictOnDelete, so skipping it is a
     * raw database error. Hence the transaction. Iterated rather than a builder
     * delete: a builder delete fires no events, so neither their observer nor the
     * audit rows for them would ever run.
     */
    public function destroy(NewsletterGroup $newsletterGroup): JsonResponse
    {
        if ($newsletterGroup->is_default) {
            throw ValidationException::withMessages([
                'newsletter_group' => 'This is the default mailing list — it is where a signup lands when no list is named. Make another list the default first.',
            ]);
        }

        DB::transaction(function () use ($newsletterGroup) {
            $newsletterGroup->subscribers()->get()->each->delete();

            $newsletterGroup->delete();
        });

        return $this->respond(null, 'Newsletter group deleted successfully');
    }
}
