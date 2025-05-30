<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Http\Requests\Admin\Settings\UpdateSettingRequest;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

class SettingsController extends Controller
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

        $settings = Setting::orderBy('group')->orderBy('key');

        if ($search) {
            $settings->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('key', 'like', '%' . $search . '%');
            });
        }

        $settings = $settings->paginate($perPage, ['*'], 'page', $page);

        return view('admin.settings.index', [
            'settings' => $settings,
            'pagination' => $this->indexService->handlePagination($settings)
        ]);
    }
    
        /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Setting $setting)
    {    
        return view('admin.settings.show', compact('setting'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Setting $setting)
    {
        if($setting->type == "menu"){
            $menus = Menu::get();            
            
            return view('admin.settings.edit', compact('setting', 'menus'));
        }else if($setting->type == "page"){
            $pages = Page::get();            

            return view('admin.settings.edit', compact('setting', 'pages'));
        }

        return view('admin.settings.edit', compact('setting'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Setting $setting, UpdateSettingRequest $request){
        $setting->update($request->validated());

        return redirect()->route('admin.settings.index')
            ->with('success','Setting updated successfully');
    }
}
