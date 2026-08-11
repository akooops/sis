@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('guidelines'))
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
            <x-panel id="guidelines-panel" :title="getLanguageKeyLocalTranslation('guidelines_page_collapse_title')">
                <div class="mb-4">
                    <label for="grade-select" class="field-label font-semibold">
                        {{ getLanguageKeyLocalTranslation('guidelines_page_table_header_grade') }}
                    </label>

                    <select id="grade-select" class="select">
                        <option value="">{{ getLanguageKeyLocalTranslation('guidelines_page_select_grade_placeholder') }}</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->getLocalTranslation('title') }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="grade-files" class="overflow-x-auto" hidden>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-[25px]">#</th>
                                <th class="w-[90%]">{{ getLanguageKeyLocalTranslation('guidelines_page_table_header_file') }}</th>
                                <th>{{ getLanguageKeyLocalTranslation('guidelines_page_table_header_option') }}</th>
                            </tr>
                        </thead>
                        <tbody id="grade-files-body"></tbody>
                    </table>
                </div>

                <p id="grade-files-empty" class="py-4 text-center text-muted" hidden>
                    {{ getLanguageKeyLocalTranslation('no_files_available') }}
                </p>
            </x-panel>
        </div>
    </div>
</section>

@endsection

@section('script')
    <script>
        (function () {
            var grades = @json($grades);
            var select = document.getElementById('grade-select');
            var table = document.getElementById('grade-files');
            var body = document.getElementById('grade-files-body');
            var empty = document.getElementById('grade-files-empty');
            var ctaLabel = @json(getLanguageKeyLocalTranslation('guidelines_page_table_cta'));

            select.addEventListener('change', function () {
                var grade = grades.find(function (item) { return String(item.id) === select.value; });
                var files = (grade && grade.files) || [];

                body.replaceChildren();
                table.hidden = files.length === 0;
                empty.hidden = !select.value || files.length > 0;

                files.forEach(function (file, index) {
                    var row = document.createElement('tr');

                    var number = document.createElement('th');
                    number.scope = 'row';
                    number.textContent = index + 1;

                    var name = document.createElement('td');
                    name.textContent = file.original_name;

                    var action = document.createElement('td');
                    var link = document.createElement('a');
                    link.href = file.url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'btn btn-sm';
                    link.innerHTML = '<i class="uil uil-angle-right-b" aria-hidden="true"></i>';
                    link.append(ctaLabel);
                    action.append(link);

                    row.append(number, name, action);
                    body.append(row);
                });
            });
        })();
    </script>
@endsection
