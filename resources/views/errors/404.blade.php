@extends('layouts.master')
@section('title', 'Page non trouvée')
@section('description', "IEC, fondé en 2020, encourage à sortir de sa zone de confort et à abandonner les excuses. Pendant deux ans, le club a organisé divers événements pour les étudiants en génie industriel et les personnes cherchant à améliorer leur parcours académique et professionnel, favorisant ainsi la croissance personnelle et l'excellence.")
@section('css')
@endsection
@section('content')
<section class="wrapper bg-secondary">
    <div class="container py-12 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <h1 class="display-1 mb-3">
                    Page non trouvée
                </h1>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

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