<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Program\ProgramData;
use App\Data\Program\ReorderProgramsData;
use App\Data\Program\StoreProgramData;
use App\Data\Program\UpdateProgramData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Program;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProgramsController extends ApiController
{
    public function index(): JsonResponse
    {
        $programs = QueryBuilder::for(Program::class)
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'subtitle', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'order', 'created_at'])
            // Display order by default: this is a menu, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ProgramData::collect($programs, PaginatedDataCollection::class), 'Programs retrieved successfully');
    }

    public function show(Program $program): JsonResponse
    {
        return $this->respond(ProgramData::from($program), 'Program retrieved successfully');
    }

    public function store(StoreProgramData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $program = Program::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'order' => Program::nextOrder(),
            'title' => [$default => $data->title],
            'subtitle' => [$default => $data->subtitle],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
        ]);

        UploadService::attach($data->thumbnail, $program, Program::THUMBNAIL_COLLECTION);

        return $this->respond(ProgramData::from($program->fresh()), 'Program created successfully', 201);
    }

    public function update(UpdateProgramData $data, Program $program): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $program->update($program->mergeTranslations(Arr::except($data->toArray(), ['thumbnail'])));

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $program, Program::THUMBNAIL_COLLECTION);
        }

        return $this->respond(ProgramData::from($program->fresh()), 'Program updated successfully');
    }

    /**
     * A program owns its streams and grades, so they go with it.
     *
     * Iterated rather than a builder delete: a builder delete fires no events, so
     * neither their observers nor the audit rows for them would ever run.
     */
    public function destroy(Program $program): JsonResponse
    {
        DB::transaction(function () use ($program) {
            $program->streams()->get()->each->delete();
            $program->grades()->get()->each->delete();

            $program->delete();
        });

        return $this->respond(null, 'Program deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderProgramsData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            Program::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Programs reordered successfully');
    }
}
