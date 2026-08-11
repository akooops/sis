@extends('layouts.master')
@section('title', transOrDefault($brand, 'title'))
@section('description', transOrDefault($brand, 'description'))
@section('canonical', route('brand', ['slug' => $brand->slug]))
@section('image', $brand->thumbnailUrl)

@section('content')

@php
    $brandTagline = transOrDefault($brand, 'tagline');
@endphp

@include('partials.page-hero', [
    'image' => $brand->thumbnailUrl,
    'title' => transOrDefault($brand, 'title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_identity_page_title'),
        'url' => route('identity'),
    ]],
    'current' => transOrDefault($brand, 'title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($brand, 'title') }}
        </h2>

        @if ($brandTagline)
            <p class="text-xl font-medium text-ink" data-aos="fade-up" data-aos-duration="1000">{{ $brandTagline }}</p>
        @endif

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($brand, 'content') !!}
        </div>

        @if ($groupedAssets->isEmpty())
            <p class="py-10 text-center text-xl font-medium text-ink">
                {{ getLanguageKeyLocalTranslation('brand_empty_assets_message') }}
            </p>
        @else
            @foreach ($groupedAssets as $group => $assets)
                @php
                    $groupLabel = getLanguageKeyLocalTranslation('brand_group_' . $group) ?: ucfirst($group);
                @endphp

                <hr class="mb-6 mt-8 border-line">

                <h3 class="mb-4" data-aos="fade-up" data-aos-duration="1000">{{ $groupLabel }}</h3>

                @if ($assets->every(fn ($asset) => $asset->fileType === 'image'))
                    {{-- Image grid: logos, scent imagery, … --}}
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"
                        data-aos="fade-up" data-aos-duration="1000">
                        @foreach ($assets as $asset)
                            <div class="card h-full">
                                <figure class="overlay">
                                    <a href="{{ $asset->url }}" target="_blank" rel="noopener">
                                        <img class="w-full object-cover" src="{{ $asset->url }}" alt="{{ $asset->name }}" />
                                    </a>
                                </figure>

                                <div class="flex items-center justify-between gap-2 p-3">
                                    <span class="text-sm">{{ $asset->name }}</span>
                                    <a href="{{ $asset->url }}" download class="btn btn-sm px-3"
                                        aria-label="{{ getLanguageKeyLocalTranslation('brand_download_button') }}">
                                        <i class="uil uil-import" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @php $isAudio = $assets->every(fn ($asset) => $asset->fileType === 'audio'); @endphp

                    <div class="overflow-x-auto" data-aos="fade-up" data-aos-duration="1000">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th class="w-[25px]">#</th>
                                    <th class="{{ $isAudio ? '' : 'w-3/5' }}">
                                        {{ getLanguageKeyLocalTranslation('brand_table_header_name') }}
                                    </th>
                                    <th class="{{ $isAudio ? 'w-2/5' : '' }}">
                                        {{ $isAudio ? '' : getLanguageKeyLocalTranslation('brand_table_header_size') }}
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($assets as $key => $asset)
                                    <tr>
                                        <th scope="row">{{ $key + 1 }}</th>
                                        <td>{{ $asset->name }}</td>
                                        <td>
                                            @if ($isAudio)
                                                <audio controls preload="none" class="max-h-10 w-full">
                                                    <source src="{{ $asset->url }}" type="{{ $asset->file->type ?? 'audio/mpeg' }}">
                                                </audio>
                                            @else
                                                {{ $asset->fileSize }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ $asset->url }}" download class="btn btn-sm">
                                                <i class="uil uil-import" aria-hidden="true"></i>
                                                {{ getLanguageKeyLocalTranslation('brand_download_button') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</section>

@endsection
