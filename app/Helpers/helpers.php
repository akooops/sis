<?php

use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Program;
use App\Models\Setting;

function getCurrentLanguage(){
    $local = app()->getLocale();

    $language = Language::where('code', $local)->first();

    if(!$language)
        $language = Language::where('is_default')->first();

    return $language;
}

function getLanguages() {
    return cache()->remember('all-languages', 3600, function() {
        return Language::orderBy('is_default', 'DESC')->get();
    });
}

function getPrograms() {
    return cache()->remember('all-programs', 3600, function() {
        return Program::orderBy('order')->get();
    });
}

function getProgramWithStreams($id) {
    if (! $id) {
        return null;
    }

    return cache()->remember("program-with-streams-{$id}", 3600, function() use ($id) {
        return Program::with('streams')->find($id);
    });
}

function getMenu($name) {
    return cache()->remember("menu-{$name}", 3600, function() use ($name) {
        return Menu::whereNull('facility_id')->where('name', $name)->first();
    });
}

function getPage($id) {
    return cache()->remember("page-{$id}", 3600, function() use ($id) {
        return Page::find($id);
    });
}

function getPageBySlug($slug) {
    return cache()->remember("page-{$slug}", 3600, function() use ($slug) {
        return Page::where('slug', $slug)->first();
    });
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

/**
 * Local translation with a graceful fallback to the default language
 * (instead of the raw "field.code" placeholder) — used by the facility
 * mini-site views so untranslated content still renders.
 */
function transOrDefault($model, string $field) {
    $value = $model->getLocalTranslation($field);

    if (! str_starts_with($value ?? '', $field . '.')) {
        return $value;
    }

    $default = Language::where('is_default', true)->first();

    if ($default) {
        $fallback = $model->getTranslation($field, $default->code);

        if (! str_starts_with($fallback ?? '', $field . '.')) {
            return $fallback;
        }
    }

    return '';
}

function currentFacility() {
    return app()->bound('currentFacility') ? app('currentFacility') : null;
}

/**
 * Generate a URL for a facility mini-site route, using whichever access mode
 * (wildcard subdomain or /facilities/{slug} prefix) served the current request.
 */
function facilityRoute($name, $params = []) {
    $prefix = request()->attributes->get('facilityRoutePrefix');

    $facility = $params['facility'] ?? null;
    unset($params['facility']);

    if (! $prefix) {
        // Outside a facility request (e.g. main-site facilities listing):
        // prefer the subdomain when configured and the facility has one.
        $facility = $facility ?? currentFacility();

        if ($facility && $facility->domain && config('facilities.root_domain')) {
            return route('facility.domain.' . $name, array_merge($params, ['facilityDomain' => $facility->domain]));
        }

        if ($facility) {
            return route('facility.' . $name, array_merge($params, ['facilitySlug' => $facility->slug]));
        }
    }

    return route(($prefix ?: 'facility.') . $name, $params);
}

