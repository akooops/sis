@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="grid gap-6 lg:grid-cols-4">

                <aside class="lg:col-span-1">
                    <form action="{{ route('web.site.achievements.index') }}">
                        <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                            @lang('site.achievements.filters.search')
                        </h4>

                        <input name="search" value="{{ $search }}" type="search" class="input mb-6"
                            aria-label="@lang('site.achievements.filters.search')">

                        <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                            @lang('site.achievements.filters.categories')
                        </h4>

                        <select name="category" class="select mb-6" aria-label="@lang('site.achievements.filters.categories')">
                            <option value="">@lang('site.achievements.filters.all_categories')</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->code }}" @selected($category === $item->code)>
                                    {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                </option>
                            @endforeach
                        </select>

                        <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                            @lang('site.achievements.filters.years')
                        </h4>

                        <select name="year" class="select mb-6" aria-label="@lang('site.achievements.filters.years')">
                            <option value="">@lang('site.achievements.filters.all_years')</option>
                            @foreach ($years as $item)
                                <option value="{{ $item }}" @selected((string) $year === (string) $item)>{{ $item }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm w-full">@lang('common.search')</button>

                        @if ($search || $category || $year)
                            <a class="btn btn-sm btn-outline mt-2 block text-center"
                                href="{{ route('web.site.achievements.index') }}">
                                @lang('site.achievements.filters.clear')
                            </a>
                        @endif
                    </form>
                </aside>

                <div class="lg:col-span-3">
                    @if ($achievements->isEmpty())
                        <h3 class="mb-2 text-brand">@lang('site.achievements.empty.title')</h3>
                        <p class="text-muted">@lang('site.achievements.empty.body')</p>
                    @else
                        @include('site::partials.achievement-timeline', [
                            'achievementsByYear' => $achievementsByYear,
                        ])
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
