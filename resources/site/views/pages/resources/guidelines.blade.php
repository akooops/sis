{{--
    Grade guidelines.

    The OLD SITE'S SHAPE — page copy, then a collapsible "Guidelines" panel
    holding a grade select above a #/Files/Options table — with one change: the
    table shows EVERY file by default, with the grade beside each, and the select
    narrows it. The old page rendered an empty panel until you picked a grade,
    so a visitor who only wanted "the KG3 one" had to know it existed first.

    Filtering is server-side through `?grade=`, unlike the old page's inline
    script that shipped every grade and its files as JSON and swapped rows in the
    DOM. That makes a filtered view a shareable URL, keeps it working with the
    script blocked, and matches how every other filter on this site works.

    Everything here comes from ResourcesController::guidelines(); `$files` is
    already flattened to one row per file.
--}}
@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.menu', ['menu' => $page->menu])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                {{ $page->getTranslation('title', $site->locale(), true) ?: $page->name }}
            </h2>

            <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

            @if ($page->getTranslation('content', $site->locale(), true))
                @include('site::partials.content.content-styles', ['model' => $page])

                {{-- id="page-content" is the scope hook the admin's own CSS
                     targets. See site::partials.content.content-styles. --}}
                <div id="page-content" class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
                    {!! $page->getTranslation('content', $site->locale(), true) !!}
                </div>
            @endif

            <div class="pt-6" data-aos="fade-up" data-aos-duration="2000">
                <div class="card">
                    <div class="card-header">
                        <button type="button" class="panel-toggle" data-toggle="collapse"
                            data-target="#guidelines-panel" aria-expanded="true" aria-controls="guidelines-panel">
                            @lang('site.guidelines.panel')
                        </button>
                    </div>

                    <div id="guidelines-panel" class="collapse-panel is-open" aria-hidden="false">
                        <div>
                            <div class="card-body">
                                @if ($grades->isNotEmpty())
                                    {{-- A GET form, so a filtered view is a URL.
                                         data-auto-submit submits it on change and
                                         removes the button; the button is the
                                         no-JS path and stays in the markup for
                                         the page whose script never loaded. --}}
                                    <form class="mb-4" action="{{ route('web.site.guidelines') }}" data-auto-submit>
                                        <label for="grade-select" class="field-label font-semibold">
                                            @lang('site.guidelines.table.grade')
                                        </label>

                                        <select id="grade-select" name="grade" class="select">
                                            <option value="">@lang('site.guidelines.all_grades')</option>
                                            @foreach ($grades as $item)
                                                <option value="{{ $item->id }}" @selected($grade === $item->id)>
                                                    {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-sm mt-2"
                                            data-auto-submit-fallback>@lang('common.search')</button>
                                    </form>
                                @endif

                                @if ($files->isEmpty())
                                    <p class="py-4 text-center text-muted">@lang('site.guidelines.empty')</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="w-[25px]">#</th>
                                                    <th class="w-[60%]">@lang('site.table.file')</th>
                                                    <th>@lang('site.guidelines.table.grade')</th>
                                                    <th>@lang('site.table.action')</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($files as $file)
                                                    <tr>
                                                        <th scope="row">{{ $loop->iteration }}</th>
                                                        <td>{{ $file['name'] }}</td>
                                                        <td>{{ $file['grade'] }}</td>
                                                        <td>
                                                            <a class="btn btn-sm" href="{{ $file['url'] }}" download>
                                                                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                                                                @lang('common.download')
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
