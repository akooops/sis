@extends('layouts.master')
@section('title', $achievement->getLocalTranslation('title'))
@section('description', $achievement->getLocalTranslation('description'))
@section('canonical', route('achievement', ['slug' => $achievement->slug]))
@section('image', $achievement->thumbnailUrl)

@section('content')

@include('partials.page-hero', [
    'image' => $achievement->thumbnailUrl,
    'title' => $achievement->getLocalTranslation('title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_achievements_page_title'),
        'url' => route('achievements'),
    ]],
    'current' => $achievement->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
        <div class="grid gap-6 lg:grid-cols-3">

            <article class="card lg:col-span-2">
                <figure class="overlay">
                    <img class="w-full object-cover" src="{{ $achievement->thumbnailUrl }}"
                        alt="{{ $achievement->getLocalTranslation('title') }}">
                </figure>

                <div class="card-body">
                    <h2 class="text-3xl font-black uppercase leading-[35px] text-brand">
                        {{ $achievement->getLocalTranslation('title') }}
                    </h2>

                    <hr class="mb-4 mt-2 border-line">

                    <div class="prose">
                        {!! $achievement->getLocalTranslation('content') !!}
                    </div>
                </div>

                <div class="card-footer">
                    <ul class="post-meta">
                        <li>
                            <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                            <span>{{ $achievement->created_at->format('Y-m-d') }}</span>
                        </li>
                        @if ($achievement->getLocalTranslation('done_by'))
                            <li>
                                <i class="uil uil-user" aria-hidden="true"></i>
                                <span>{{ $achievement->getLocalTranslation('done_by') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </article>

            <aside>
                @include('partials.media-list', [
                    'title' => getLanguageKeyLocalTranslation('sidebar_achievements_same_year_title'),
                    'items' => $sameYearAchievements,
                    'route' => 'achievement',
                ])
            </aside>
        </div>
    </div>
</section>

@endsection
