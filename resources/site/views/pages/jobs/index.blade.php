{{-- Careers listing, built as the news listing is (site::pages.articles.index):
     image cards in a two-thirds column with the filters in the aside. A vacancy
     is browsed the same way an article is, so it gets the same furniture — the
     card classes, the coloured category chip and the pager all already exist. --}}
@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    {{-- ONE URL BUILDER for the category chips, which are the only filters that
         are links rather than form controls. Each chip states only what it
         CHANGES and inherits the other three, so picking a category cannot
         silently drop the work mode the visitor chose; a null override drops
         that key, which is how the active chip CLEARS itself. `page` is
         deliberately absent — narrowing the list has to land on page 1 rather
         than on a page number the narrowed set may no longer have. --}}
    @php($filterUrl = fn (array $changes = []) => route('web.site.jobs.index', array_filter($changes + [
        'search' => $search,
        'category' => $category,
        'employment_type' => $employmentType,
        'work_mode' => $workMode,
    ], fn ($value) => filled($value))))

    @php($hasFilters = $search || $category || $employmentType || $workMode)

    <section>
        <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
            <div class="grid gap-6 lg:grid-cols-6">

                <div class="lg:col-span-4">
                    @if ($jobs->isEmpty())
                        <p class="text-muted">@lang('jobs.empty')</p>
                    @else
                        <div class="mb-8 grid auto-rows-fr gap-4 md:grid-cols-2">
                            @foreach ($jobs as $job)
                                @php($url = route('web.site.jobs.show', ['slug' => $job->slug]))
                                @php($label = $job->getTranslation('title', $site->locale(), true) ?: $job->name)

                                <article class="card media-card">
                                    <figure class="overlay media-figure">
                                        <a href="{{ $url }}">
                                            <img src="{{ $job->thumbnail_url ?: asset('assets/site/images/placeholder.png') }}"
                                                alt="{{ $label }}" loading="lazy">
                                        </a>
                                    </figure>

                                    <div class="card-body">
                                        @if ($job->category)
                                            {{-- The chip wears the category's own
                                                 colour; .badge falls back to brand
                                                 when the row has none. --}}
                                            <span class="badge mb-2" style="--badge-color: {{ $job->category->color }}">
                                                {{ $job->category->getTranslation('title', $site->locale(), true) ?: $job->category->name }}
                                            </span>
                                        @endif

                                        <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                            <a class="hover:text-brand-soft" href="{{ $url }}">{{ $label }}</a>
                                        </h2>

                                        {{-- Keyed by the enum value already on the model, so
                                             there is no match block here and a new value
                                             surfaces as a missing key rather than silently
                                             rendering nothing. --}}
                                        <div class="mb-2 flex flex-wrap gap-2">
                                            <span class="badge">@lang('jobs.employment_type.'.$job->employment_type)</span>
                                            <span class="badge">@lang('jobs.work_mode.'.$job->work_mode)</span>
                                            @if ($job->education_level)
                                                <span class="badge">@lang('jobs.education_level.'.$job->education_level)</span>
                                            @endif
                                        </div>

                                        <p class="mb-0">{{ $job->getTranslation('description', $site->locale(), true) }}</p>
                                    </div>

                                    <div class="card-footer">
                                        <ul class="post-meta">
                                            <ul class="post-meta">
                                                <li>
                                                    <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                                    <span>{{ $job->published_at?->translatedFormat('j M Y') }}</span>
                                                </li>
                                            </ul>
                                        </ul>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @include('site::partials.pagination', ['paginator' => $jobs])
                    @endif
                </div>

                {{-- Same aside as the achievements listing: ONE GET form holding the
                     search box, the category chips and a <select> per enum filter,
                     submitted by a single button. A select rather than a list of
                     links because the form is what carries the other filters —
                     four separate link lists each had to rebuild the whole query
                     string to avoid dropping the other three. --}}
                <aside class="lg:col-span-2">
                    <form action="{{ route('web.site.jobs.index') }}">
                        <input name="search" value="{{ $search }}" type="search" class="input mb-6"
                            placeholder="@lang('jobs.search.placeholder')" aria-label="@lang('jobs.search.placeholder')">

                        @if ($categories->isNotEmpty())
                            <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                                @lang('jobs.filters.categories')
                            </h4>

                            {{-- Coloured chips rather than a stacked list of links, so
                                 the filter reads the same as the chip printed on each
                                 vacancy card. The selected one is ringed in its OWN
                                 colour instead of recoloured, which would have
                                 detached it from the category it names. --}}
                            <ul class="mb-8 flex flex-wrap gap-2">
                                @foreach ($categories as $item)
                                    @php($isActive = $category === $item->code)

                                    <li>
                                        {{-- The active chip links back to the listing
                                             without a category, so clicking it clears
                                             the filter while keeping the other three. --}}
                                        <a class="badge {{ $isActive ? 'is-active' : '' }}"
                                            style="--badge-color: {{ $item->color }}"
                                            href="{{ $filterUrl(['category' => $isActive ? null : $item->code]) }}"
                                            @if ($isActive) aria-current="true" @endif>
                                            {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- The chips are LINKS, not inputs, so the chosen category
                                 has to ride the form as a hidden field or submitting a
                                 search would silently clear it. --}}
                            @if ($category)
                                <input type="hidden" name="category" value="{{ $category }}">
                            @endif
                        @endif

                        {{-- Employment type and work mode differ only in the key they
                             filter on, and the catalogue is keyed to match: the
                             heading is jobs.filters.<key>, the empty option is
                             jobs.filters.all_<key> and every option's label is
                             jobs.<key>.<value>. One loop renders both, so the two
                             selects cannot drift apart in behaviour or in markup. --}}
                        @php($lists = [
                            ['key' => 'employment_type', 'values' => $employmentTypes, 'current' => $employmentType],
                            ['key' => 'work_mode', 'values' => $workModes, 'current' => $workMode],
                        ])

                        @foreach ($lists as $list)
                            @if (filled($list['values']))
                                <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">
                                    @lang('jobs.filters.'.$list['key'])
                                </h4>

                                <select name="{{ $list['key'] }}" class="select mb-6"
                                    aria-label="@lang('jobs.filters.'.$list['key'])">
                                    {{-- The empty value is the cleared state, so it is
                                         selected exactly when nothing is chosen. --}}
                                    <option value="">@lang('jobs.filters.all_'.$list['key'])</option>
                                    @foreach ($list['values'] as $value)
                                        <option value="{{ $value }}" @selected($list['current'] === $value)>
                                            @lang('jobs.'.$list['key'].'.'.$value)
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        @endforeach

                        <button type="submit" class="btn btn-sm w-full">@lang('jobs.search.button')</button>

                        @if ($hasFilters)
                            <a class="btn btn-sm btn-outline mt-2 block text-center"
                                href="{{ route('web.site.jobs.index') }}">
                                @lang('jobs.filters.clear')
                            </a>
                        @endif
                    </form>
                </aside>
            </div>
        </div>
    </section>
@endsection
