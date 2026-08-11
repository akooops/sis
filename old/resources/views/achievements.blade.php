@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('achievements'))

@section('content')

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <p class="mb-6 text-xl font-medium text-ink" data-aos="fade-up" data-aos-duration="2000">
            {{ getLanguageKeyLocalTranslation('achievements_page_description') }}
        </p>

        <div class="grid gap-6 pt-6 lg:grid-cols-3" data-aos="fade-up" data-aos-duration="2000">
            <div class="lg:col-span-2">
                @if ($achievementsByYear->count() > 0)
                    @include('partials.achievement-timeline', [
                        'achievementsByYear' => $achievementsByYear,
                        'breakpoints' => '{"1200": {"slidesPerView": 2}}',
                    ])
                @endif
            </div>

            <aside class="flex flex-col gap-8">
                <div>
                    <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                        {{ getLanguageKeyLocalTranslation('sidebar_achievements_search_title') }}
                    </h4>

                    <form action="{{ route('achievements') }}">
                        <input name="search" value="{{ request()->get('search') }}" type="search" class="input"
                            placeholder="{{ getLanguageKeyLocalTranslation('sidebar_achievements_search_placeholder') }}">
                    </form>
                </div>

                <div>
                    <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                        {{ getLanguageKeyLocalTranslation('sidebar_achievements_categories_title') }}
                    </h4>

                    <form action="{{ route('achievements') }}" method="get">
                        @foreach (request()->only(['search', 'year']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach

                        <select name="category" class="select" onchange="this.form.submit()">
                            <option value="">{{ getLanguageKeyLocalTranslation('sidebar_achievements_all_categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                    {{ $category->getLocalTranslation('title') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div>
                    <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                        {{ getLanguageKeyLocalTranslation('sidebar_achievements_years_title') }}
                    </h4>

                    <form action="{{ route('achievements') }}" method="get">
                        @foreach (request()->only(['search', 'category']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach

                        <select name="year" class="select" onchange="this.form.submit()">
                            <option value="">{{ getLanguageKeyLocalTranslation('sidebar_achievements_all_years') }}</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</section>

@endsection
