<?php

namespace App\Http\Controllers\Admin;

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

        return view('admin.menus.index', [
            'menus' => $menus,
            'pagination' => $this->indexService->handlePagination($menus)
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.menus.create');
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
    
        return redirect()->route('admin.menus.index')
                        ->with('success','Menu created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu)
    {    
        return view('admin.menus.show', compact('menu'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Menu $menu)
    {
        return view('admin.menus.edit', compact('menu'));
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
    
        return redirect()->route('admin.menus.index')
                        ->with('success','Menu updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')
                        ->with('success','Menu deleted successfully');
    }
}
