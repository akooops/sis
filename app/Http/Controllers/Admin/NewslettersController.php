<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Newsletters\StoreNewsletterRequest;
use App\Http\Requests\Admin\Newsletters\UpdateNewsletterRequest;
use App\Http\Requests\Admin\Newsletters\UpdateNewsletterTranslationRequest;
use App\Models\Language;
use App\Models\Media;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewslettersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $newsletters = Newsletter::latest();

        if ($search) {
            $newsletters->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $newsletters = $newsletters->paginate($perPage, ['*'], 'newsletter', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'newsletters' => $newsletters->items(),
                'pagination' => $this->indexService->handlePagination($newsletters),
            ]);
        }

        return inertia('Newsletters/Index');
    }

    /**
     * Show the newsletter for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return inertia('Newsletters/Create', compact('defaultLanguage'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreNewsletterRequest $request)
    {
        $newsletter = Newsletter::create($request->validated());

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach ($newsletter->getTranslatableFields() as $field) {
            $newsletter->setTranslation($field, $defaultLanguage->code, $request->input($field));
        }

        $media = null;

        if ($request->hasFile('file')) {
            // Get MIME type
            $mimeType = $request->file('file')->getMimeType();

            // Determine file category using match expression
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default => 'document',
            };

            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => $type,
                ]
            ));

            $defaultLanguage = Language::where([
                'is_default' => true,
            ])->first();

            foreach ($media->getTranslatableFields() as $field) {
                $media->setTranslation($field, $defaultLanguage->code, $request->input($field));
            }

            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        }

        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Newsletter', $newsletter->id, true);

        return inertia('Newsletters/Index', [
            'success' => 'Newsletter created successfully!',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Newsletter $newsletter)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $newsletter->getTranslatableFieldsByLanguages();

        return inertia('Newsletters/Show', [
            'newsletter' => $newsletter,
            'languages' => $languages,
            'translations' => $translations,
        ]);
    }

    /**
     * Show the newsletter for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Newsletter $newsletter)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $newsletter->getTranslatableFieldsByLanguages();

        return inertia('Newsletters/Edit', [
            'newsletter' => $newsletter,
            'languages' => $languages,
            'translations' => $translations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Newsletter $newsletter, UpdateNewsletterRequest $request)
    {
        $newsletter->update($request->validated());

        $media = null;

        if ($request->hasFile('file')) {
            // Get MIME type
            $mimeType = $request->file('file')->getMimeType();

            // Determine file category using match expression
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default => 'document',
            };

            $media = Media::create(array_merge(
                $request->validated(),
                [
                    'type' => $type,
                ]
            ));

            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        }

        if ($media) {
            if ($newsletter->file) {
                $newsletter->file->detach();
            }
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Newsletter', $newsletter->id, true);
        }

        return inertia('Newsletters/Index', [
            'success' => 'Newsletter updated successfully!',
        ]);
    }

    public function updateTranslation(Newsletter $newsletter, UpdateNewsletterTranslationRequest $request)
    {
        $language = Language::find($request->language_id);

        foreach ($newsletter->getTranslatableFields() as $field) {
            $newsletter->setTranslation($field, $language->code, $request->input($field));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Newsletter updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Newsletter deleted successfully');
    }
}
