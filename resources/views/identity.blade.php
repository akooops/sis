@extends('layouts.master')
@section('title', transOrDefault($page, 'title'))
@section('description', transOrDefault($page, 'description'))
@section('canonical', route('identity'))

@section('content')

<section class="wrapper banners-section">
    <div class="swiper-container"
        data-margin="0"
        data-autoplay="true"
        data-autoplaytime="7000"
        data-nav="true"
        data-dots="true"
        data-items="1">

        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $page->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{ transOrDefault($page, 'title') }}
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
                <li class="breadcrumb-item text-uppercase active" aria-current="page">
                    {{ transOrDefault($page, 'title') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<section class="wrapper page-content-section">
    <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($page, 'title') }}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">

        <div class="w-100" data-aos="fade-up" data-aos-duration="2000">
            {!! transOrDefault($page, 'content') !!}
        </div>

        <hr class="mt-4 mb-8" data-aos="fade-up" data-aos-duration="1500">

        <div class="row gx-8 gy-6">
            @foreach ($brands as $brand)
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-duration="1000">
                    <div class="card h-100">
                        <figure class="card-img-top overflow-hidden hover-scale">
                            <a href="{{ route('brand', ['slug' => $brand->slug]) }}">
                                <img src="{{ $brand->thumbnailUrl }}" alt="{{ transOrDefault($brand, 'title') }}" />
                            </a>
                        </figure>

                        <div class="card-body p-4">
                            <h3 class="mb-2">
                                {{ transOrDefault($brand, 'title') }}
                            </h3>

                            @php $brandTagline = transOrDefault($brand, 'tagline'); @endphp
                            @if($brandTagline)
                                <p class="text-primary mb-2">{{ $brandTagline }}</p>
                            @endif

                            <p class="truncate-3-lines">
                                {{ transOrDefault($brand, 'description') }}
                            </p>

                            <a href="{{ route('brand', ['slug' => $brand->slug]) }}" class="btn btn-sm btn-primary rounded mt-2">
                                {{ getLanguageKeyLocalTranslation('identity_page_view_button') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
