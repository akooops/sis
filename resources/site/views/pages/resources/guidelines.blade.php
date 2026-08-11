@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            @if ($grades->isEmpty())
                <p class="text-muted">@lang('site.guidelines.empty')</p>
            @else
                @foreach ($grades as $grade)
                    @php($files = $grade->getMedia(\App\Models\Grade::GUIDELINES_COLLECTION))

                    <div class="card">
                        <div class="card-header">
                            <button type="button" class="panel-toggle" data-toggle="collapse"
                                data-target="#grade-{{ $grade->id }}" aria-expanded="true"
                                aria-controls="grade-{{ $grade->id }}">
                                {{ $grade->getTranslation('title', $site->locale(), true) ?: $grade->name }}
                            </button>
                        </div>

                        <div id="grade-{{ $grade->id }}" class="collapse-panel is-open" aria-hidden="false">
                            <div>
                                <div class="card-body">
                                    @if ($files->isEmpty())
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
                                                @foreach ($files as $file)
                                                    <tr>
                                                        <td>{{ $file->name }}</td>
                                                        <td>{{ round($file->size / 1024) }} KB</td>
                                                        <td>
                                                            <a class="btn btn-sm" href="{{ $file->url }}" download>
                                                                <i class="uil uil-download-alt" aria-hidden="true"></i>
                                                                @lang('common.download')
                                                            </a>
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
                @endforeach
            @endif
        </div>
    </section>
@endsection
