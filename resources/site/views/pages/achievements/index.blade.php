@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="grid gap-6 lg:grid-cols-6">
                <div class="lg:col-span-4">
                    @if ($achievements->isEmpty())
                        <h3 class="mb-2 text-brand">@lang('site.achievements.empty.title')</h3>
                        <p class="text-muted">@lang('site.achievements.empty.body')</p>
                    @else
                        @include('site::partials.ui.achievement-timeline', [
                            'achievementsByYear' => $achievementsByYear,
                            'breakpoints' => '{"992": {"slidesPerView": 2}, "1200": {"slidesPerView": 2}}'
                        ])
                    @endif
                </div>

                <aside class="lg:col-span-2">
                    <form action="{{ route('web.site.achievements.index') }}">
                        <input name="search" value="{{ $search }}" type="search" class="input mb-6"
                            placeholder="@lang('site.achievements.filters.search')" aria-label="@lang('site.achievements.filters.search')">

                        @if ($categories->isNotEmpty())
                            <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                                @lang('site.achievements.filters.categories')
                            </h4>

                            {{-- Coloured chips rather than a stacked list of links, so
                                the filter reads the same as the chip printed on each
                                article card. The selected one is ringed in its OWN
                                colour instead of recoloured, which would have
                                detached it from the category it names.

                                A row, not a column: the chips are short and their
                                colour is the thing being scanned. --}}
                            <ul class="mb-8 flex flex-wrap gap-2">
                                @foreach ($categories as $item)
                                    @php($isActive = $category === $item->code)

                                    <li>
                                        {{-- The active chip links back to the unfiltered
                                            listing, so clicking it clears the filter. --}}
                                        <a class="badge {{ $isActive ? 'is-active' : '' }}"
                                            style="--badge-color: {{ $item->color }}"
                                            href="{{ $isActive ? route('web.site.articles.index') : route('web.site.articles.index', ['category' => $item->code]) }}"
                                            @if ($isActive) aria-current="true" @endif>
                                            {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

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
            </div>
        </div>
    </section>
@endsection
