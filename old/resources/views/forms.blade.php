@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('forms'))
@section('image', $page->thumbnailUrl)

@section('content')

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <div class="pt-6" data-aos="fade-up" data-aos-duration="2000">
            <x-panel id="forms-panel" :title="getLanguageKeyLocalTranslation('forms_page_collapse_title')">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-[25px]">#</th>
                                <th class="w-[90%]">{{ getLanguageKeyLocalTranslation('forms_page_table_header_file') }}</th>
                                <th>{{ getLanguageKeyLocalTranslation('forms_page_table_header_option') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($forms as $key => $form)
                                <tr>
                                    <th scope="row">{{ $key + 1 }}</th>
                                    <td>{{ $form->getLocalTranslation('title') }}</td>
                                    <td>
                                        <a href="{{ $form->formUrl }}" target="_blank" rel="noopener" class="btn btn-sm">
                                            <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                                            {{ getLanguageKeyLocalTranslation('forms_page_table_cta') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>
    </div>
</section>

@endsection
