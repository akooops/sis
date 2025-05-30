@extends('layouts.master')
@section('title', $grade->getLocalTranslation('title'))
@section('description', $grade->getLocalTranslation('description'))
@section('canonical', route('grade', ['slug' => $grade->slug]))
@section('image', $grade->thumbnailUrl)
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ $grade->thumbnailUrl  }}'); background-size: cover">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h1 class="display-1 fs-48 mt-12 text-white animate__animated animate__slideInDown animate__delay-1s">
                    {{$grade->getLocalTranslation('title')}}
                </h1>
                <p class="lead fs-16 fw-semibold lh-sm mb-7 text-white animate__animated animate__slideInRight animate__delay-2s">                   
                    {{$grade->program->getLocalTranslation('title')}}                                         
                </p>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

<section class="wrapper bg-light-primary">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($grades as $menuGrade)
                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2 text-uppercase fs-14 text-center fw-semibold 
                    {{ $grade->id === $menuGrade->id ? 'active' : '' }}"
                    href="{{route('grade', ['slug' => $menuGrade->slug])}}">
                        {{$menuGrade->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<section class="wrapper bg-light">
   <div class="container py-3 py-md-5">
      <nav class="d-inline-block" aria-label="breadcrumb">
         <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a class="text-uppercase" href="{{route('index')}}">
                    {{getLanguageKeyLocalTranslation('breadcrumbs_index_page_title')}}
                </a>
            </li>            
            <li class="breadcrumb-item text-uppercase active" aria-current="page">
                {{$grade->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>


<section class="wrapper bg-light">
<div class="container py-12 py-md-5">
    
    <div class="row">
        <h2 class="display-5 mb-3">
            {{$grade->getLocalTranslation('title')}}
        </h2>

        <p class="lead fs-md">
            {{$grade->getLocalTranslation('description')}}
        </p>

        <hr class="mt-2 mb-4">

        <div class="w-100 page-content">
            <x-markdown>
                {{ $grade->getLocalTranslation('content') }}
            </x-markdown>
        </div>
    </div>

    <div class="row pt-6">
        <div id="accordion-3" class="accordion-wrapper">
            <div class="card accordion-item">
                <div class="card-header" id="accordion-heading-3-1">
                    <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#grades-collapse" aria-expanded="false" aria-controls="grades-collapse">
                        {{getLanguageKeyLocalTranslation('grade_page_collapse_title')}}
                    </button>
                </div>
                <!-- /.card-header -->
                <div id="grades-collapse" class="collapse show">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col" width="25px">#</th>
                                    <th scope="col" width="90%">
                                        {{getLanguageKeyLocalTranslation('grade_page_table_header_file')}}
                                    </th>
                                    <th scope="col">
                                        {{getLanguageKeyLocalTranslation('grade_page_table_header_option')}}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grade->files as $key => $file)
                                    <tr>
                                        <th scope="row">
                                            {{$key + 1}}
                                        </th>
                                        <td>
                                           {{$file->original_name}}
                                        </td>
                                        <td>
                                            <a href="{{$file->url}}" target="_blank" class="btn btn-sm btn-primary rounded">
                                                {{getLanguageKeyLocalTranslation('grade_page_table_cta')}}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
@endsection