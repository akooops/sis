<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Grade\GradeData;
use App\Data\Grade\ReorderGradesData;
use App\Data\Grade\StoreGradeData;
use App\Data\Grade\UpdateGradeData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Grade;
use App\Models\Language;
use App\Models\Program;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GradesController extends ApiController
{
    public function index(): JsonResponse
    {
        $grades = QueryBuilder::for(Grade::class)
            ->with(['media', 'program.media'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('program_id'),
                $this->searchTranslations(
                    ['id', 'name'],
                    ['title'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            // Display order by default: grades are a sequence, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(GradeData::collect($grades, PaginatedDataCollection::class), 'Grades retrieved successfully');
    }

    /** Nested list for one program — the program is the route, so it is not re-loaded. */
    public function forProgram(Program $program): JsonResponse
    {
        $grades = QueryBuilder::for(Grade::query()->where('program_id', $program->getKey()))
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->searchTranslations(
                    ['id', 'name'],
                    ['title'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(GradeData::collect($grades, PaginatedDataCollection::class), 'Program grades retrieved successfully');
    }

    public function show(Grade $grade): JsonResponse
    {
        return $this->respond(GradeData::from($grade->load(['media', 'program'])), 'Grade retrieved successfully');
    }

    public function store(StoreGradeData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $grade = Grade::create([
            'program_id' => $data->program_id,
            'name' => $data->name,
            'title' => [$default => $data->title],
            'order' => Grade::nextOrder($data->program_id),
        ]);

        // The guidelines, in submitted order — sync writes order_column from it.
        UploadService::sync($data->guidelines, $grade, Grade::GUIDELINES_COLLECTION);

        return $this->respond(GradeData::from($grade->fresh()->load(['media', 'program'])), 'Grade created successfully', 201);
    }

    public function update(UpdateGradeData $data, Grade $grade): JsonResponse
    {
        $attributes = Arr::except($data->toArray(), ['guidelines']);

        // Moved to another program: its old position means nothing there.
        if ($data->program_id !== $grade->program_id) {
            $attributes['order'] = Grade::nextOrder($data->program_id);
        }

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $grade->update($grade->mergeTranslations($attributes));

        // A dropped id detaches back to the library, it is not deleted.
        UploadService::sync($data->guidelines, $grade, Grade::GUIDELINES_COLLECTION);

        return $this->respond(GradeData::from($grade->fresh()->load(['media', 'program'])), 'Grade updated successfully');
    }

    public function destroy(Grade $grade): JsonResponse
    {
        $grade->delete();

        return $this->respond(null, 'Grade deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderGradesData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            Grade::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Grades reordered successfully');
    }
}
