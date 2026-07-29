@extends('layouts.master')
@section('title', transOrDefault($brand, 'title'))
@section('description', transOrDefault($brand, 'description'))
@section('canonical', route('brand', ['slug' => $brand->slug]))
@section('image', $brand->thumbnailUrl)

@section('content')

<section class="wrapper banners-section">
    <div class="swiper-container" data-margin="0" data-nav="false" data-dots="false" data-items="1">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $brand->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{ transOrDefault($brand, 'title') }}
                                    </h1>
                                </div>
                                <!--/.col -->
                            </div>
                            <!--/.row -->
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.container -->
                </div>
                <!--/.swiper-slide -->
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->

<section class="wrapper">
    <div class="container py-3 py-md-5">
        <nav class="d-inline-block" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{ route('index') }}">
                        {{ getLanguageKeyLocalTranslation('breadcrumbs_index_page_title') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-uppercase" href="{{ route('identity') }}">
                        {{ getLanguageKeyLocalTranslation('breadcrumbs_identity_page_title') }}
                    </a>
                </li>
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ transOrDefault($brand, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($brand, 'title') }}
        </h2>

        @php $brandTagline = transOrDefault($brand, 'tagline'); @endphp
        @if($brandTagline)
            <p class="lead" data-aos="fade-up" data-aos-duration="1000">{{ $brandTagline }}</p>
        @endif

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1000">

        <div class="w-100 page-content" data-aos="fade-up" data-aos-duration="1500">
            {!! transOrDefault($brand, 'content') !!}
        </div>

        @if($groupedAssets->isEmpty())
            <p class="lead text-center py-10">{{ getLanguageKeyLocalTranslation('brand_empty_assets_message') }}</p>
        @else
            @foreach ($groupedAssets as $group => $assets)
                @php
                    $groupLabel = getLanguageKeyLocalTranslation('brand_group_' . $group);
                    $groupLabel = $groupLabel !== '' ? $groupLabel : ucfirst($group);
                @endphp

                <hr class="mt-8 mb-6">

                <h3 class="mb-4" data-aos="fade-up" data-aos-duration="1000">{{ $groupLabel }}</h3>

                @if($assets->every(fn ($asset) => $asset->fileType === 'image'))
                    <!-- Image grid (logos, scent imagery, ...) -->
                    <div class="row gx-4 gy-4" data-aos="fade-up" data-aos-duration="1000">
                        @foreach ($assets as $asset)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card h-100">
                                    <figure class="card-img-top rounded hover-scale overflow-hidden mb-0">
                                        <a href="{{ $asset->url }}" target="_blank">
                                            <img src="{{ $asset->url }}" alt="{{ $asset->name }}" />
                                        </a>
                                    </figure>
                                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                        <span class="fs-14">{{ $asset->name }}</span>
                                        <a href="{{ $asset->url }}" download class="btn btn-sm btn-primary rounded px-3">
                                            <i class="uil uil-import"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($assets->every(fn ($asset) => $asset->fileType === 'audio'))
                    <!-- Audio library -->
                    <div class="table-responsive" data-aos="fade-up" data-aos-duration="1000">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" width="25px">#</th>
                                    <th scope="col">{{ getLanguageKeyLocalTranslation('brand_table_header_name') }}</th>
                                    <th scope="col" width="40%"></th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assets as $key => $asset)
                                    <tr>
                                        <th scope="row">{{ $key + 1 }}</th>
                                        <td>{{ $asset->name }}</td>
                                        <td>
                                            <audio controls preload="none" class="w-100" style="max-height: 40px">
                                                <source src="{{ $asset->url }}" type="{{ $asset->file->type ?? 'audio/mpeg' }}">
                                            </audio>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ $asset->url }}" download class="btn btn-sm btn-primary">
                                                <i class="uil uil-import me-2"></i>
                                                {{ getLanguageKeyLocalTranslation('brand_download_button') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Download table (guidelines, fonts, documents, mixed) -->
                    <div class="table-responsive" data-aos="fade-up" data-aos-duration="1000">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" width="25px">#</th>
                                    <th scope="col" width="60%">{{ getLanguageKeyLocalTranslation('brand_table_header_name') }}</th>
                                    <th scope="col">{{ getLanguageKeyLocalTranslation('brand_table_header_size') }}</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assets as $key => $asset)
                                    <tr>
                                        <th scope="row">{{ $key + 1 }}</th>
                                        <td>{{ $asset->name }}</td>
                                        <td>{{ $asset->fileSize }}</td>
                                        <td class="text-end">
                                            <a href="{{ $asset->url }}" download class="btn btn-sm btn-primary">
                                                <i class="uil uil-import me-2"></i>
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
