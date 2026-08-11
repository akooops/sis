@extends('layouts.master')
@section('title', $event->getLocalTranslation('title'))
@section('description', $event->getLocalTranslation('description'))
@section('canonical', route('event', ['slug' => $event->slug]))
@section('image', $event->thumbnailUrl)

@section('content')

@include('partials.page-hero', [
    'image' => $event->thumbnailUrl,
    'title' => $event->getLocalTranslation('title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_events_page_title'),
        'url' => route('events'),
    ]],
    'current' => $event->getLocalTranslation('title'),
])

@include('partials.page-body', [
    'title' => $event->getLocalTranslation('title'),
    'subtitle' => $event->starts_at . ' - ' . $event->ends_at,
    'content' => $event->getLocalTranslation('content'),
])

@endsection
