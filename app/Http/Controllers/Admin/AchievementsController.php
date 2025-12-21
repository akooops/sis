<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Achievements\StoreAchievementRequest;
use App\Http\Requests\Admin\Achievements\UpdateAchievementRequest;
use App\Http\Requests\Admin\Achievements\UpdateAchievementTranslationRequest;
use App\Models\Achievement;
use App\Models\AchievementCategory;
use App\Models\Language;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AchievementsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $achievements = Achievement::with('category')->orderBy('achievement_date', 'desc');

        if ($search) {
            $achievements->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
                
                // Search by year from achievement_date
                if (is_numeric($search) && strlen($search) === 4) {
                    $query->orWhereYear('achievement_date', $search);
                }
            });
        }

        $achievements = $achievements->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'achievements' => $achievements->items(),
                'pagination' => $this->indexService->handlePagination($achievements)
            ]);
        }

        return inertia('Achievements/Index');
    }

    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return inertia('Achievements/Create', compact('defaultLanguage'));
    }

    public function store(StoreAchievementRequest $request)
    {
        $achievement = Achievement::create(array_merge(
            $request->validated(),
            ['slug' => Str::slug($request->slug)]
        ));

        $defaultLanguage = Language::where('is_default', true)->first();

        foreach ($achievement->getTranslatableFields() as $field) {
            $achievement->setTranslation($field, $defaultLanguage->code, $request->input($field));
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
                    'type' => $type
                ]
            ));

            $defaultLanguage = Language::where([
                'is_default' => true,
            ])->first();

            foreach($media->getTranslatableFields() as $field){
                $media->setTranslation($field, $defaultLanguage->code, $request->input($field));    
            }
            
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Achievement', $achievement->id, true);

        return inertia('Achievements/Index', [
            'success' => 'Achievement created successfully!'
        ]);
    }

    public function show(Achievement $achievement)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $achievement->getTranslatableFieldsByLanguages();

        return inertia('Achievements/Show', [
            'achievement' => $achievement,
            'languages' => $languages,
            'translations' => $translations
        ]);
    }

    public function edit(Achievement $achievement)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $achievement->getTranslatableFieldsByLanguages();

        return inertia('Achievements/Edit', compact('achievement', 'languages', 'translations'));
    }

    public function update(Achievement $achievement, UpdateAchievementRequest $request)
    {
        $achievement->update(array_merge(
            $request->validated(),
            ['slug' => Str::slug($request->slug)]
        ));

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
                    'type' => $type
                ]
            ));
            
            $file = $this->fileService->upload($request->file('file'), 'App\\Models\\Media', $media->id);
        } else {
            $media = Media::find($request->input('media_id'));
        }
    
        if($media){
            if($achievement->file) $achievement->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Achievement', $achievement->id, true);
        }
    }

    public function updateTranslation(Achievement $achievement, UpdateAchievementTranslationRequest $request)
    {
        $language = Language::find($request->language_id);

        foreach ($achievement->getTranslatableFields() as $field) {
            $achievement->setTranslation($field, $language->code, $request->input($field));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Achievement translation updated successfully',
        ]);
    }

    public function destroy(Achievement $achievement)
    {
        $achievement->delete();

        return redirect()->route('admin.achievements.index')
            ->with('success', 'Achievement deleted successfully');
    }
}
