@extends('layouts.master')
@section('title', 'Page non trouvée')
@section('description', "IEC, fondé en 2020, encourage à sortir de sa zone de confort et à abandonner les excuses. Pendant deux ans, le club a organisé divers événements pour les étudiants en génie industriel et les personnes cherchant à améliorer leur parcours académique et professionnel, favorisant ainsi la croissance personnelle et l'excellence.")
@section('css')
@endsection
@section('content')
<section class="wrapper bg-dark page-main-section" style="background-image: url('{{ URL::asset('assets/img/photos/banner-1.jpg')}}')">
    <div class="container page-main-container">
        <div class="row h-100">
            <div class="col-md-10 offset-md-1 col-lg-7 offset-lg-0 col-xl-6 col-xxl-5 text-center text-lg-start justify-content-center align-self-center align-items-start">
                <h2 class="display-1 fs-48 mb-4 text-white animate__animated animate__slideInDown animate__delay-1s">
                    Welcome to saud international schools
                </h2>
                <p class="lead fs-18 lh-sm mb-0 text-white animate__animated animate__slideInRight animate__delay-2s">                   
                    Learning Today . . . Leading Tomorrow.                                         
                </p>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!--/.container -->
</section>

<section class="wrapper">
    <div class="container py-12 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <p class="lead mb-7 px-md-12 px-lg-5 px-xl-7">
                    La page que vous recherchez n'est pas disponible ou a été déplacée. Essayez une autre page ou accédez à la page d'accueil avec le bouton ci-dessous.
                </p>

                <a class="btn btn-md btn-primary rounded me-2" href="{{route('index')}}">Page d'accueil </a>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

@endsection
@section('script')
@endsection