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

        $settings = Setting::with('page')->orderBy('group')->orderBy('key');

        if ($search) {
            $settings->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('key', 'like', '%' . $search . '%');
            });
        }

        $settings = $settings->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'settings' => $settings->items(),
                'pagination' => $this->indexService->handlePagination($settings)
            ]);
        }

        return inertia('Settings/Index');
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

        cache()->forget("setting-{$setting->key}");

        return response()->json([
            'status' => 'success',
            'message' => 'Setting updated successfully',
        ]);
    }
}
