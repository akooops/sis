@extends('layouts.master')
@section('title', transOrDefault($page, 'title'))
@section('description', transOrDefault($page, 'description'))
@section('canonical', route('identity'))

@section('content')

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => transOrDefault($page, 'title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => transOrDefault($page, 'title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ transOrDefault($page, 'title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! transOrDefault($page, 'content') !!}
        </div>

        <hr class="mb-8 mt-4 border-line" data-aos="fade-up" data-aos-duration="1500">

        @include('partials.tile-grid', [
            'cta' => getLanguageKeyLocalTranslation('identity_page_view_button'),
            'height' => 'h-[240px]',
            'tiles' => $brands->map(fn ($brand) => [
                'image' => $brand->thumbnailUrl,
                'title' => transOrDefault($brand, 'title'),
                'tagline' => transOrDefault($brand, 'tagline'),
                'description' => transOrDefault($brand, 'description'),
                'url' => route('brand', ['slug' => $brand->slug]),
            ]),
        ])
    </div>
</section>

@endsection
