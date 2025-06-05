<?php

use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Menu;
use App\Models\Page;
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

function getMenu($id){
    return Menu::find($id);
}

function getPage($id){
    return Page::find($id);
}

function getSetting($key) {
    return cache()->remember("setting-{$key}", 3600, function() use ($key) {
        return Setting::where('key', $key)->first();
    });
}

function getLanguageKeyLocalTranslation($key){
    $locale = app()->getLocale();
    $cacheKey = "language-key-{$key}-{$locale}";

    return cache()->remember($cacheKey, 3600, function() use ($key) {
        $languageKey = LanguageKey::where('key', $key)->first();
        return $languageKey ? $languageKey->getLocalTranslation('content') : '';
    });
}

