@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $brand->thumbnail_url,
        'title' => $brand->getTranslation('title', $site->locale(), true) ?: $brand->name,
    ])

    @include('site::partials.breadcrumb')

    @include('site::partials.page-body', [
        'title' => $brand->getTranslation('title', $site->locale(), true) ?: $brand->name,
        'subtitle' => $brand->getTranslation('description', $site->locale(), true),
        'content' => $brand->getTranslation('content', $site->locale(), true),
    ])

    <section>
        <div class="container pb-14">
            @foreach ($brand->assetGroups as $group)
                <div class="card mb-4">
                    <div class="card-header">
                        <button type="button" class="panel-toggle" data-toggle="collapse"
                            data-target="#brand-group-{{ $group->id }}" aria-expanded="true"
                            aria-controls="brand-group-{{ $group->id }}">
                            {{ $group->getTranslation('title', $site->locale(), true) ?: $group->name }}
                        </button>
                    </div>

                    <div id="brand-group-{{ $group->id }}" class="collapse-panel is-open" aria-hidden="false">
                        <div>
                            <div class="card-body">
                                @if ($group->assets->isEmpty())
                                    <p class="text-muted mb-0">@lang('site.identity.empty')</p>
                                @else
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>@lang('site.identity.table.name')</th>
                                                <th>@lang('common.file_size')</th>
                                                <th>@lang('site.table.action')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group->assets as $asset)
                                                @php($file = $asset->getMedia(\App\Models\BrandAsset::FILE_COLLECTION)->first())

                                                <tr>
                                                    <td>{{ $asset->getTranslation('title', $site->locale(), true) ?: $asset->name }}</td>
                                                    <td>{{ $file ? round($file->size / 1024) . ' KB' : '—' }}</td>
                                                    <td>
                                                        @if ($file)
                                                            <a class="btn btn-sm" href="{{ $file->url }}" download>
                                                                <i class="uil uil-download-alt" aria-hidden="true"></i>
                                                                @lang('site.identity.download')
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
            @endforeach
        </div>
    </section>
@endsection
