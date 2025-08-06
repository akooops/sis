@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('page', ['slug' => $page->slug]))
@section('image', $page->thumbnailUrl)
@section('css')
@endsection
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
                                        {{$page->getLocalTranslation('title')}}
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

@if($page->menu)
<section class="wrapper page-menu-section">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($page->menu->items as $menuItem)
                @php
                    $menuUrl = $menuItem->page
                        ? route('page', ['slug' => $menuItem->page->slug])
                        : $menuItem->url;
                    $currentUrl = url()->current();
                @endphp

                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2 {{ $currentUrl === $menuUrl ? 'active' : '' }}" href="{{ $menuUrl }}">
                        {{$menuItem->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="wrapper">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('index')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_index_page_title')}}
                </a>
            </li>
            
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$page->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>

<section class="wrapper page-content-section">
   <div class="container pt-6 pb-12">
        <h2 data-aos="fade-up" data-aos-duration="1000">
            {{$page->getLocalTranslation('title')}}
        </h2>

        <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">

        <div class="w-100" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <div class="row pt-6" data-aos="fade-up" data-aos-duration="2000">
            <div id="accordion-3" class="accordion-wrapper">
                <div class="card accordion-item">
                    <div class="card-header" id="accordion-heading-3-1">
                        <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#guidelines-collapse" aria-expanded="false" aria-controls="guidelines-collapse">
                            {{getLanguageKeyLocalTranslation('guidelines_page_collapse_title')}}
                        </button>
                    </div>
                    <!-- /.card-header -->
                    <div id="guidelines-collapse" class="collapse show">
                        <div class="card-body">
                            <!-- Grade Selection -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="grade-select" class="form-label fw-semibold">
                                        {{getLanguageKeyLocalTranslation('guidelines_page_table_header_grade')}}
                                    </label>
                                    <select id="grade-select" class="form-select" onchange="showGradeFiles()">
                                        <option value="">{{getLanguageKeyLocalTranslation('guidelines_page_select_grade_placeholder')}}</option>
                                        @foreach ($grades as $grade)
                                            <option value="{{ $grade->id }}" data-grade-index="{{ $loop->index }}">
                                                {{$grade->getLocalTranslation('title')}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Grade Files Table -->
                            <div id="grade-files-table" class="table-responsive" style="display: none;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="25px">#</th>
                                            <th scope="col" width="90%">
                                                {{getLanguageKeyLocalTranslation('guidelines_page_table_header_file')}}
                                            </th>
                                            <th scope="col">
                                                {{getLanguageKeyLocalTranslation('guidelines_page_table_header_option')}}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="grade-files-tbody">
                                        <!-- Files will be loaded here dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- No files message -->
                            <div id="no-files-message" class="text-center py-4" style="display: none;">
                                <p class="text-muted">{{getLanguageKeyLocalTranslation('no_files_available')}}</p>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.collapse -->
                </div>
            </div>
        </div>
        <!-- /.container -->
    </div>
</section>

@endsection
@section('script')
<script>
// Pass grades data to JavaScript
const gradesData = @json($grades);

function showGradeFiles() {
    const gradeSelect = document.getElementById('grade-select');
    const gradeFilesTable = document.getElementById('grade-files-table');
    const gradeFilesTbody = document.getElementById('grade-files-tbody');
    const noFilesMessage = document.getElementById('no-files-message');
    
    const selectedGradeId = gradeSelect.value;
    
    // Hide all elements initially
    gradeFilesTable.style.display = 'none';
    noFilesMessage.style.display = 'none';
    
    if (!selectedGradeId) {
        return; // No grade selected
    }
    
    // Find the selected grade from the data
    const selectedGrade = gradesData.find(grade => grade.id == selectedGradeId);
    
    if (!selectedGrade || !selectedGrade.files || selectedGrade.files.length === 0) {
        // Show no files message
        noFilesMessage.style.display = 'block';
        return;
    }
    
    // Display files in table
    gradeFilesTbody.innerHTML = '';
    
    selectedGrade.files.forEach((file, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <th scope="row">${index + 1}</th>
            <td>${file.original_name}</td>
            <td>
                <a href="${file.url}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="uil uil-angle-right-b me-2"></i>
                    {{getLanguageKeyLocalTranslation('guidelines_page_table_cta')}}
                </a>
            </td>
        `;
        gradeFilesTbody.appendChild(row);
    });
    
    gradeFilesTable.style.display = 'block';
}
</script>
@endsection