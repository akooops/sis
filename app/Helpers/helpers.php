<?php

use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Menu;
use App\Models\Setting;

function getCurrentLanguage(){
    $local = app()->getLocale();

    $language = Language::where('code', $local)->first();

    if(!$language)
        $language = Language::where('is_default')->first();

    return $language;
}

function getLanguages(){
    return Language::orderBy('is_default', 'DESC')->get();
}

function getSetting($key) {
    return Setting::where('key', $key)->first();
}

function getMenu($id){
    return Menu::find($id);
}

function getLanguageKeyLocalTranslation($key){
    $languageKey = LanguageKey::where('key', $key)->first();

    return $languageKey ? $languageKey->getLocalTranslation('content') : '';
}
