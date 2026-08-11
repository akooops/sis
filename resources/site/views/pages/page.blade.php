@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    {{-- A page that belongs to a menu renders that menu as a section nav, which
         is how a cluster of sibling pages navigates between itself. --}}
    @include('site::partials.page-menu', ['menu' => $menu])

    @include('site::partials.breadcrumb')

    @include('site::partials.page-body', [
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
        'subtitle' => $page->getTranslation('description', $site->locale(), true),
        'content' => $page->getTranslation('content', $site->locale(), true),
    ])
@endsection
