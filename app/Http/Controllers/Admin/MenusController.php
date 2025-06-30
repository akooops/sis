<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Menus\DeleteMenuRequest;
use App\Http\Requests\Admin\Menus\StoreMenuRequest;
use App\Http\Requests\Admin\Menus\UpdateMenuRequest;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\Route;

class MenusController extends Controller
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

        $menus = Menu::latest();

        if ($search) {
            $menus->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $menus = $menus->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'menus' => $menus->items(), 
                'pagination' => $this->indexService->handlePagination($menus)
            ]);
        }

        return inertia('Menus/Index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Menus/Create');
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMenuRequest $request)
    {
        $menu = Menu::create($request->validated());
    
        return inertia('Menus/Index', [
            'success' => 'Menu created successfully!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu)
    {    
        return inertia('Menus/Show', compact('menu'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Menu $menu)
    {
        return inertia('Menus/Edit', compact('menu'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Menu $menu, UpdateMenuRequest $request)
    {
        $menu->update($request->validated());
    
        cache()->forget("menu-{$menu->name}");

        return inertia('Menus/Index', [
            'success' => 'Menu updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu, DeleteMenuRequest $request)
    {
        $menu->delete();

        cache()->forget("menu-{$menu->name}");

        return redirect()->route('admin.menus.index')
                        ->with('success','Menu deleted successfully');
    }
}
