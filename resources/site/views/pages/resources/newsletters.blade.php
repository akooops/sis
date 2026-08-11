@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="panel-toggle" data-toggle="collapse" data-target="#newsletters-panel"
                        aria-expanded="true" aria-controls="newsletters-panel">
                        @lang('site.newsletters.panel')
                    </button>
                </div>

                <div id="newsletters-panel" class="collapse-panel is-open" aria-hidden="false">
                    <div>
                        <div class="card-body">
                            @if ($newsletters->isEmpty())
                                <p class="text-muted mb-0">@lang('site.guidelines.empty')</p>
                            @else
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>@lang('site.table.file')</th>
                                            <th>@lang('common.file_size')</th>
                                            <th>@lang('site.table.action')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($newsletters as $newsletter)
                                            @php($file = $newsletter->getMedia(\App\Models\Newsletter::FILE_COLLECTION)->first())

                                            <tr>
                                                <td>{{ $newsletter->getTranslation('title', $site->locale(), true) ?: $newsletter->name }}</td>
                                                <td>{{ $file ? round($file->size / 1024) . ' KB' : '—' }}</td>
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
