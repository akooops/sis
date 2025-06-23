<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MenuItems\OrderMenuItemsRequest;
use App\Http\Requests\Admin\MenuItems\StoreMenuItemRequest;
use App\Http\Requests\Admin\MenuItems\UpdateMenuItemRequest;
use App\Http\Requests\Admin\MenuItems\UpdateMenuItemTranslationRequest;
use App\Models\Album;
use App\Models\Article;
use App\Models\Event;
use App\Models\Grade;
use App\Models\JobPosting;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MenuItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Menu $menu)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $menuItems = $menu->allItems()->latest();

        if ($search) {
            $menuItems->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $menuItems = $menuItems->paginate($perPage, ['*'], 'menuItem', $page);

        return view('admin.menu-items.index', [
            'menu' => $menu,
            'menuItems' => $menuItems,
            'pagination' => $this->indexService->handlePagination($menuItems)
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Menu $menu)
    {
        $defaultLanguage = Language::where('is_default', true)->first();

        $linkableItems = $this->getLinkableItems();

        return view('admin.menu-items.create', compact('defaultLanguage', 'menu', 'linkableItems'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMenuItemRequest $request, Menu $menu)
    {
        $validatedData = $request->validated();

        $validatedData['order'] = $menu->items()->max('order') + 1;

        if($request->input('external')){
            $validatedData['linkable_id'] = null;
            $validatedData['linkable_type'] = null;
        }else{
            $validatedData['url'] = null;
        }

        $menuItem = MenuItem::create(array_merge(
            $validatedData,
            [
                'menu_id' => $menu->id,
            ]
        ));

        $menuItem->save();

        $defaultLanguage = Language::where([
            'is_default' => true,
        ])->first();

        foreach($menuItem->getTranslatableFields() as $field){
            $menuItem->setTranslation($field, $defaultLanguage->code, $request->input($field));    
        }

        return redirect()->route('admin.menu-items.index', ['menu' => $menu->id])
                        ->with('success','MenuItem created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(MenuItem $menuItem)
    {    
        $languages = Language::orderBy('is_default', 'DESC')->get();

        return view('admin.menu-items.show', compact('menuItem', 'languages'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(MenuItem $menuItem)
    {
        $languages = Language::orderBy('is_default', 'DESC')->get();
        $linkableItems = $this->getLinkableItems();

        return view('admin.menu-items.edit', compact('menuItem', 'languages', 'linkableItems'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(MenuItem $menuItem, UpdateMenuItemRequest $request)
    {
        $validatedData = $request->validated();

        if($request->input('external')){
            $validatedData['linkable_id'] = null;
            $validatedData['linkable_type'] = null;
        }else{
            $validatedData['url'] = null;
        }

        $menuItem->update($validatedData);

        return redirect()->route('admin.menu-items.index', ['menu' => $menuItem->menu_id])
                        ->with('success','MenuItem updated successfully');
    }

    public function updateTranslation(MenuItem $menuItem, UpdateMenuItemTranslationRequest $request){
        $language = Language::find($request->language_id);

        foreach($menuItem->getTranslatableFields() as $field){
            $menuItem->setTranslation($field, $language->code, $request->input($field));    
        }

        return response()->json([
            'status' => 'success',
            'message' => 'MenuItem updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuItem $menuItem)
    {
        $menuItem->delete();

        return redirect()->route('admin.menu-items.index', ['menu' => $menuItem->menu_id])
                        ->with('success','MenuItem deleted successfully');
    }

    public function orderPage(Menu $menu)
    {
        $menuItems = $menu->items;

        return view('admin.menu-items.order', compact('menu', 'menuItems'));
    }

    public function order(OrderMenuItemsRequest $request, Menu $menu)
    {
        foreach ($request->order as $item) {
            MenuItem::where('id', $item['id'])
                ->update([
                    'order' => $item['order'],
                    'menu_item_id' => $item['menu_item_id']
            ]);
        }
            
        return response()->json([
            'status' => 'success',
        ]);
    }

    private function getLinkableItems()
    {
        return [
            'Page' => Page::get(),
            'Program' => Program::get(),
            'Article' => Article::get(),
            'Album' => Album::get(),
            'Event' => Event::get(),
            'Grade' => Grade::get(),
            'JobPosting' => JobPosting::get(),
        ];
    }
}
