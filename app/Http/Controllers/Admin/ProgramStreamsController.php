<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProgramStreams\OrderProgramStreamsRequest;
use App\Http\Requests\Admin\ProgramStreams\StoreProgramStreamRequest;
use App\Http\Requests\Admin\ProgramStreams\UpdateProgramStreamRequest;
use App\Http\Requests\Admin\ProgramStreams\UpdateProgramStreamTranslationRequest;
use App\Models\Language;
use App\Models\Program;
use App\Models\ProgramStream;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramStreamsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Program $program)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $programStreams = ProgramStream::where('program_id', $program->id)->latest();

        if ($search) {
            $programStreams->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $programStreams = $programStreams->paginate($perPage, ['*'], 'programStream', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'programStreams' => $programStreams->items(),
                'pagination' => $this->indexService->handlePagination($programStreams)
            ]);
        }

        return inertia('ProgramStreams/Index', [
            'program' => $program,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Program $program)
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return inertia('ProgramStreams/Create', [
            'defaultLanguage' => $defaultLanguage,
            'program' => $program,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProgramStreamRequest $request, Program $program)
    {
        $programStream = ProgramStream::create(array_merge(
            $request->validated(),
            [
                'program_id' => $program->id,
                'slug' => Str::slug($request->slug),
                'order' => ProgramStream::where('program_id', $program->id)->max('order') + 1,
            ]
        ));

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach ($programStream->getTranslatableFields() as $field) {
            $programStream->setTranslation($field, $defaultLanguage->code, $request->input($field));
        }

        cache()->forget("program-with-streams-{$program->id}");

        return inertia('ProgramStreams/Index', [
            'success' => 'Stream created successfully!',
            'program' => $program,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProgramStream $programStream)
    {
        $programStream->load('program');

        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $programStream->getTranslatableFieldsByLanguages();

        return inertia('ProgramStreams/Show', [
            'programStream' => $programStream,
            'languages' => $languages,
            'translations' => $translations,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProgramStream $programStream)
    {
        $programStream->load('program');

        $languages = Language::orderBy('is_default', 'DESC')->get();
        $translations = $programStream->getTranslatableFieldsByLanguages();

        return inertia('ProgramStreams/Edit', [
            'programStream' => $programStream,
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
    public function update(ProgramStream $programStream, UpdateProgramStreamRequest $request)
    {
        $programStream->update(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug),
            ]
        ));

        cache()->forget("program-with-streams-{$programStream->program_id}");

        return inertia('ProgramStreams/Index', [
            'success' => 'Stream updated successfully!',
            'program' => $programStream->program,
        ]);
    }

    public function updateTranslation(ProgramStream $programStream, UpdateProgramStreamTranslationRequest $request)
    {
        $language = Language::find($request->language_id);

        foreach ($programStream->getTranslatableFields() as $field) {
            $programStream->setTranslation($field, $language->code, $request->input($field));
        }

        cache()->forget("program-with-streams-{$programStream->program_id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Stream updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProgramStream $programStream)
    {
        $programId = $programStream->program_id;

        $programStream->delete();

        cache()->forget("program-with-streams-{$programId}");

        return redirect()->route('admin.program-streams.index', ['program' => $programId])
            ->with('success', 'Stream deleted successfully');
    }

    public function orderPage(Program $program)
    {
        $programStreams = ProgramStream::where('program_id', $program->id)
            ->orderBy('order')
            ->get();

        return inertia('ProgramStreams/Order', [
            'program' => $program,
            'programStreams' => $programStreams,
        ]);
    }

    public function order(OrderProgramStreamsRequest $request, Program $program)
    {
        foreach ($request->order as $item) {
            ProgramStream::where('id', $item['id'])
                ->where('program_id', $program->id)
                ->update([
                    'order' => $item['order'],
                ]);
        }

        cache()->forget("program-with-streams-{$program->id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Streams ordered successfully',
        ]);
    }
}
