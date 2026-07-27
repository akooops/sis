<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Stream\ReorderStreamsData;
use App\Data\Stream\StoreStreamData;
use App\Data\Stream\StreamData;
use App\Data\Stream\UpdateStreamData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Program;
use App\Models\Stream;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StreamsController extends ApiController
{
    public function index(): JsonResponse
    {
        $streams = QueryBuilder::for(Stream::class)
            ->with('program.media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('program_id'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'order', 'created_at'])
            // Display order by default: streams are a strip under their program.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(StreamData::collect($streams, PaginatedDataCollection::class), 'Streams retrieved successfully');
    }

    /** Nested route: the program comes from the URL, overwriting any filter the client sent. */
    public function forProgram(Program $program): JsonResponse
    {
        request()->merge(['filter' => array_merge((array) request()->input('filter', []), ['program_id' => $program->id])]);

        return $this->index();
    }

    public function show(Stream $stream): JsonResponse
    {
        return $this->respond(StreamData::from($stream->load('program')), 'Stream retrieved successfully');
    }

    public function store(StoreStreamData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $stream = Stream::create([
            'program_id' => $data->program_id,
            'name' => $data->name,
            'slug' => $data->slug,
            'color' => $data->color,
            'order' => Stream::nextOrder($data->program_id),
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'cta' => [$default => $data->cta],
        ]);

        return $this->respond(StreamData::from($stream->load('program')), 'Stream created successfully', 201);
    }

    public function update(UpdateStreamData $data, Stream $stream): JsonResponse
    {
        $attributes = $data->toArray();

        // Moved to another program: take a position that program has free, or it
        // would land on one already held over there.
        if ($data->program_id !== $stream->program_id) {
            $attributes['order'] = Stream::nextOrder($data->program_id);
        }

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $stream->update($stream->mergeTranslations($attributes));

        return $this->respond(StreamData::from($stream->fresh()->load('program')), 'Stream updated successfully');
    }

    public function destroy(Stream $stream): JsonResponse
    {
        $stream->delete();

        return $this->respond(null, 'Stream deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list — one program's list, since order is per program.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderStreamsData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            Stream::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Streams reordered successfully');
    }
}
