<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Document\DocumentData;
use App\Data\Document\StoreDocumentData;
use App\Data\Document\UpdateDocumentData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Document;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DocumentsController extends ApiController
{
    public function index(): JsonResponse
    {
        $documents = QueryBuilder::for(Document::class)
            // The DTO reads the file off the media relation on every row.
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->searchTranslations(['id', 'name'], ['title'], Language::enabledCodes()),
            ])
            ->allowedSorts(['id', 'name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(DocumentData::collect($documents, PaginatedDataCollection::class), 'Documents retrieved successfully');
    }

    public function show(Document $document): JsonResponse
    {
        return $this->respond(DocumentData::from($document->load('media')), 'Document retrieved successfully');
    }

    public function store(StoreDocumentData $data): JsonResponse
    {
        $document = Document::create([
            'name' => $data->name,
            'title' => [Language::defaultCode() => $data->title],
        ]);

        UploadService::attach($data->file, $document, Document::FILE_COLLECTION);

        return $this->respond(DocumentData::from($document->fresh()->load('media')), 'Document created successfully', 201);
    }

    public function update(UpdateDocumentData $data, Document $document): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $document->update($document->mergeTranslations([
            'name' => $data->name,
            'title' => $data->title,
        ]));

        if (! $data->file instanceof Optional && $data->file) {
            UploadService::attach($data->file, $document, Document::FILE_COLLECTION);
        }

        return $this->respond(DocumentData::from($document->fresh()->load('media')), 'Document updated successfully');
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return $this->respond(null, 'Document deleted successfully');
    }
}
