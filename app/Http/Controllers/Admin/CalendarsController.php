<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Calendars\StoreCalendarRequest;
use App\Http\Requests\Admin\Calendars\UpdateCalendarRequest;
use App\Http\Requests\Admin\Calendars\UpdateCalendarTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Calendar;
use App\Models\Media;

class CalendarsController extends Controller
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

        $calendars = Calendar::latest();

        if ($search) {
            $calendars->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $calendars = $calendars->paginate($perPage, ['*'], 'calendar', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'calendars' => $calendars->items(),
                'pagination' => $this->indexService->handlePagination($calendars)
            ]);
        }

        return inertia('Calendars/Index');
    }
    
    /**
     * Show the calendar for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return inertia('Calendars/Create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCalendarRequest $request)
    {
        if($request->input('is_active')){
            Calendar::where('is_active', true)->update(['is_active' => false]);
        }

        $calendar = Calendar::create($request->validated());
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($calendar->getTranslatableFields() as $field){
            $calendar->setTranslation($field, $defaultLanguage->code, $request->input($field));    
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
        }
    
        $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Calendar', $calendar->id, true);

        return inertia('Calendars/Index', [
            'success' => 'Calendar created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Calendar $calendar)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $calendar->getTranslatableFieldsByLanguages();

        return inertia('Calendars/Show', [
            'calendar' => $calendar,
            'languages' => $languages,
            'translations' => $translations
        ]);
    }
    
    /**
     * Show the calendar for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        public function edit(Calendar $calendar)    
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $calendar->getTranslatableFieldsByLanguages();

        return inertia('Calendars/Edit', [
            'calendar' => $calendar,
            'languages' => $languages,
            'translations' => $translations
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Calendar $calendar, UpdateCalendarRequest $request)
    {
        if($request->input('is_active')){
            Calendar::where('is_active', true)->update(['is_active' => false]);
        }

        $calendar->update($request->validated());
    
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
        }
    
        if($media){
            if($calendar->file) $calendar->file->detach();
            $file = $this->fileService->duplicateMediaFile($media, 'App\\Models\\Calendar', $calendar->id, true);
        }

        return inertia('Calendars/Index', [
            'success' => 'Calendar updated successfully!'
        ]);
    }

    public function updateTranslation(Calendar $calendar, UpdateCalendarTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($calendar->getTranslatableFields() as $field){
            $calendar->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Calendar updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        if($calendar->is_active){
            $randomCalendar = Calendar::where('is_active', false)->inRandomOrder()->first();
            if($randomCalendar) $randomCalendar->update(['is_active' => true]);
        }
        
        $calendar->delete();

        return redirect()->route('admin.calendars.index')
                        ->with('success','Calendar deleted successfully');
    }
}
