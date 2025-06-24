@extends('layouts.master')
@section('title', $program->getLocalTranslation('title'))
@section('description', $program->getLocalTranslation('description'))
@section('canonical', route('program', ['slug' => $program->slug]))
@section('image', $program->thumbnailUrl)
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
                    <div class="banner-img" style="background-image: url('{{ $program->thumbnailUrl }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">           
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{$program->getLocalTranslation('title')}}
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

<section class="wrapper page-menu-section">
    <div class="container py-8 d-flex justify-content-center">
        <ul class="nav justify-content-center">
            @foreach ($programs as $menuProgram)
                <li class="nav-item text-nowrap">
                    <a class="nav-link py-2
                    {{ $program->id === $menuProgram->id ? 'active' : '' }}"
                    href="{{route('program', ['slug' => $menuProgram->slug])}}">
                        {{$menuProgram->getLocalTranslation('title')}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

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
                {{$program->getLocalTranslation('title')}}
            </li>
         </ol>
      </nav>
      <!-- /nav -->
   </div>
   <!-- /.container -->
</section>


<section class="wrapper page-content-section">
   <div class="container pt-6 pb-12">
        <div class="row">
            <h2 data-aos="fade-up" data-aos-duration="1000">
                {{$program->getLocalTranslation('title')}}
            </h2>

            <p data-aos="fade-up" data-aos-duration="1500">
                {{$program->getLocalTranslation('description')}}
            </p>

            <hr class="mt-2 mb-4" data-aos="fade-up" data-aos-duration="1500">

            <div class="w-100" data-aos="fade-up" data-aos-duration="2000">
                {!! $program->getLocalTranslation('content') !!}
            </div>
        </div>

        <div class="row pt-6" data-aos="fade-up" data-aos-duration="2000">
            <div class="accordion-wrapper">
                <div class="card accordion-item">
                    <div class="card-header">
                        <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#grades-collapse" aria-expanded="false" aria-controls="grades-collapse">
                            {{getLanguageKeyLocalTranslation('program_page_collapse_title')}}
                        </button>
                    </div>
                    <!-- /.card-header -->

                    <div id="grades-collapse" class="collapse show">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="25px">#</th>
                                            <th scope="col" width="90%">
                                                {{getLanguageKeyLocalTranslation('program_page_table_header_grade')}}
                                            </th>
                                            <th scope="col">
                                                {{getLanguageKeyLocalTranslation('program_page_table_header_option')}}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($program->grades as $key => $grade)
                                            <tr>
                                                <th scope="row">
                                                    {{$key + 1}}
                                                </th>
                                                <td>
                                                    {{$grade->getLocalTranslation('title') }}
                                                </td>
                                                <td>
                                                    <a href="{{route('grade', ['slug' => $grade->slug])}}" class="btn btn-primary">
                                                        <i class="uil uil-angle-right-b me-2"></i>
                                                        {{getLanguageKeyLocalTranslation('program_page_table_cta')}}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
@endsection