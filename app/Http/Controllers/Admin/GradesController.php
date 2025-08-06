<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Grades\OrderGradesRequest;
use App\Http\Requests\Admin\Grades\StoreGradeRequest;
use App\Http\Requests\Admin\Grades\UpdateGradeRequest;
use App\Models\File;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Grade;

class GradesController extends Controller
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

        $program_id = $this->indexService->checkIfSearchEmpty($request->query('program_id'));

        $grades = Grade::with('program')->latest();

        if($program_id){
            $grades->where('program_id', $program_id);
        }

        if ($search) {
            $grades->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $grades = $grades->paginate($perPage, ['*'], 'grade', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'grades' => $grades->items(),
                'pagination' => $this->indexService->handlePagination($grades)
            ]);
        }

        return inertia('Grades/Index');   
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        return inertia('Grades/Create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGradeRequest $request)
    {
        $grade = Grade::create($request->validated());

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);
                    $file->attach($grade);
                }
            }
        }

        return inertia('Grades/Index', [
            'success' => 'Grade created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Grade $grade)
    {    
        $grade->load('files');
        $grade->load('program');

        return inertia('Grades/Show', [
            'grade' => $grade,
        ]);
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Grade $grade)
    {
        $grade->load('files');
        $grade->load('program');

        return inertia('Grades/Edit', [
            'grade' => $grade,
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Grade $grade, UpdateGradeRequest $request)
    {
        $grade->update($request->validated());

        $grade->files()->where('is_main', false)->update([
            'model_type' => null,
            'model_id' => null
        ]);

        if ($request->has('files')) {
            foreach ($request->input('files') as $file) {
                foreach ($request->input('files') as $fileId) {
                    $file = File::findOrFail($fileId);
                    $file->attach($grade);
                }
            }
        }

        return inertia('Grades/Index', [
            'success' => 'Grade updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Grade $grade)
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')
                        ->with('success','Grade deleted successfully');
    }

    public function orderPage()
    {
        $grades = Grade::orderBy('order')->get();

        return inertia('Grades/Order', compact('grades'));
    }

    public function order(OrderGradesRequest $request)
    {
        foreach ($request->order as $item) {
            Grade::where('id', $item['id'])
                ->update([
                    'order' => $item['order']
            ]);
        }
            
        return response()->json([
            'status' => 'success',
            'message' => 'Grade ordered successfully',
        ]);
    }
}
