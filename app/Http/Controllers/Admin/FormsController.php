<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Forms\DeleteFormRequest;
use App\Http\Requests\Admin\Forms\StoreFormRequest;
use App\Http\Requests\Admin\Forms\UpdateFormRequest;
use App\Http\Requests\Admin\Forms\UpdateFormTranslationRequest;
use App\Models\File;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Media;
use App\Models\Program;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class FormsController extends Controller
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

        $forms = Form::latest();

        if ($search) {
            $forms->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $forms = $forms->paginate($perPage, ['*'], 'form', $page);

        return view('admin.forms.index', [
            'forms' => $forms,
            'pagination' => $this->indexService->handlePagination($forms)
        ]);
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

        return view('admin.forms.create', compact('defaultLanguage'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFormRequest $request)
    {
        $form = Form::create(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));
        
        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($form->getTranslatableFields() as $field){
            $form->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }

        return redirect()->route('admin.forms.index')
                        ->with('success','Form created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Form $form)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.forms.show', compact('form', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Form $form)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.forms.edit', compact('form', 'languages'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Form $form, UpdateFormRequest $request)
    {
        $form->update(array_merge(
            $request->validated(),
            [
                'slug' => Str::slug($request->slug)
            ]
        ));

        return redirect()->route('admin.forms.index')
                        ->with('success','Form updated successfully');
    }

    public function updateTranslation(Form $form, UpdateFormTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($form->getTranslatableFields() as $field){
            $form->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Form updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('admin.forms.index')
                        ->with('success','Form deleted successfully');
    }
}
