@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="panel-toggle" data-toggle="collapse" data-target="#calendars-panel"
                        aria-expanded="true" aria-controls="calendars-panel">
                        @lang('site.calendars.panel')
                    </button>
                </div>

                <div id="calendars-panel" class="collapse-panel is-open" aria-hidden="false">
                    <div>
                        <div class="card-body">
                            @if ($calendars->isEmpty())
                                <p class="text-muted mb-0">@lang('site.guidelines.empty')</p>
                            @else
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>@lang('site.calendars.table.calendar')</th>
                                            <th>@lang('site.calendars.table.start')</th>
                                            <th>@lang('site.calendars.table.end')</th>
                                            <th>@lang('site.table.action')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($calendars as $calendar)
                                            {{-- `file` is a single-file media collection,
                                                 so first() is the file — no relation. --}}
                                            @php($file = $calendar->getMedia(\App\Models\Calendar::FILE_COLLECTION)->first())

                                            <tr>
                                                <td>{{ $calendar->getTranslation('title', $site->locale(), true) ?: $calendar->name }}</td>
                                                <td>{{ $calendar->start_date?->translatedFormat('j M Y') }}</td>
                                                <td>{{ $calendar->end_date?->translatedFormat('j M Y') }}</td>
                                                <td>
                                                    @if ($file)
                                                        <a class="btn btn-sm" href="{{ $file->url }}" download>
                                                            <i class="uil uil-download-alt" aria-hidden="true"></i>
                                                            @lang('common.download')
                                                        </a>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
