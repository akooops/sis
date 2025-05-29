<?php

use App\Models\Language;
use App\Models\Menu;
use App\Models\Setting;

function getLanguages(){
    return Language::orderBy('is_default', 'DESC')->get();
}

function getSetting($key) {
    return Setting::where('key', $key)->first();
}

function getMenu($id){
    return Menu::find($id);
}
