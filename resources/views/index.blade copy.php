@extends('layouts.master')
@section('title', 'Page d\'accueil')
@section('description', "IEC, fondé en 2020, encourage à sortir de sa zone de confort et à abandonner les excuses. Pendant deux ans, le club a organisé divers événements pour les étudiants en génie industriel et les personnes cherchant à améliorer leur parcours académique et professionnel, favorisant ainsi la croissance personnelle et l'excellence.")
@section('canonical', route('index'))
@section('css')
@endsection
@section('content')
<section class="wrapper">
    <div class="container pt-16 pt-lg-14">
        <div class="hero row gx-2 gy-10 align-items-center">
            <div class="hero-content col-lg-5 order-2 order-lg-0" data-cues="slideInDown" data-group="page-title" data-delay="600">
                <h1 class="hero-title display-1 fs-48 fs-lg-52 mb-5 mx-0 text-uppercase"><span class="text-primary">Industrial</span> <br/> Engineers <span class="typer text-primary text-nowrap" data-delay="100" data-words="Club."></span><span class="cursor text-primary" data-owner="typer"></span></h1>
                <p class="hero-subtitle fs-20 lh-lg mb-4">
                    Plus qu'un club. "Industrial Engineers Club" est un club du Génie Industriel.
                </p>
                <nav class="nav social social-white mb-4 ">
                    <a href="https://www.facebook.com/IEC.ENP"><i class="uil uil-facebook-f"></i></a>
                    <a href="https://www.instagram.com/iec.enp/"><i class="uil uil-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/industrial-engineers-club-iec/mycompany/"><i class="uil uil-linkedin"></i></a>
                </nav>
                <div class="d-flex mb-4" data-cues="slideInDown" data-group="page-title-buttons" data-delay="900">
                    <span>
                    <a class="btn btn-md btn-primary rounded me-2 scroll" href="#about">Savoir Plus</a>
                    </span>
                </div>
            </div>
            <!-- /column -->
            <div class="d-none d-lg-flex col-lg-6 ms-auto position-relative">
                <div class="row g-4">
                    <div class="col-6 d-flex flex-column ms-auto" data-cues="fadeIn" data-group="col-start" data-delay="900">
                        <div>
                            <img class="img-fluid rounded shadow-lg" src="{{ URL::asset('assets/img/photos/hero-2.webp')}}" style="height: 200px; object-fit: cover"  alt="" />
                        </div>
                        <div class="mt-4 d-flex justify-content-end">
                            <img class="img-fluid rounded shadow-lg" src="{{ URL::asset('assets/img/photos/hero-1.webp')}}" style="height: 152px; object-fit: cover" alt="" />
                        </div>
                    </div>
                    <!-- /column -->
                    <div class="col-6" data-cues="fadeIn" data-group="col-middle">
                        <div>
                            <img class="img-fluid rounded shadow-lg" src="{{ URL::asset('assets/img/photos/hero-3.webp')}}" style="margin-top: 48px; height: 152px; object-fit: cover" alt="" />
                        </div>
                        <div class="mt-4">
                            <img class="img-fluid rounded shadow-lg" src="{{ URL::asset('assets/img/photos/hero-4.webp')}}" style="height: 200px; object-fit: cover" alt="" />
                        </div>
                    </div>
                    <!-- /column -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
<section class="wrapper" id="about">
    <div class="container pt-14 pt-md-18">
        <div class="row gy-10 gy-sm-13 gx-lg-3 mb-16">
            <div class="col-12 col-md-8 col-lg-5 pe-2 pe-md-8">
                <div class="position-relative">
                    <a href="./assets/media/movie.mp4" class="btn btn-circle btn-primary btn-play ripple mx-auto mb-6 position-absolute" style="top:50%; left: 50%; transform: translate(-50%,-50%); z-index:3;" data-glightbox><i class="icn-caret-right"></i></a>
                    <figure class="rounded shadow-lg"><img src="{{ URL::asset('assets/img/photos/about9.jpg')}}" alt=""></figure>
                </div>
                <!-- /div -->
            </div>
            <!-- /column -->
            <!--/column -->
            <div class="col-lg-7" >
                <h2 class="fs-16 text-uppercase text-line text-primary mb-3">À propos de nous</h2>
                <h3 class="display-5 lh-lg mb-7">IEC est bien plus qu'un simple club, il représente le Génie Industriel.</h3>
                <div class="accordion accordion-wrapper" id="accordionExample">
                    <div class="card plain accordion-item">
                        <div class="card-header" id="headingOne">
                            <button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne"> Notre histoire </button>
                        </div>
                        <!--/.card-header -->
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="card-body">
                                <p class="text-gray collpase-content">
                                    IEC, fondé en 2020, encourage à sortir de sa zone de confort et à abandonner les excuses. Pendant deux ans, le club a organisé divers événements pour les étudiants en génie industriel et les personnes cherchant à améliorer leur parcours académique et professionnel, favorisant ainsi la croissance personnelle et l'excellence.
                                </p>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.accordion-collapse -->
                    </div>
                    <!--/.accordion-item -->
                    <div class="card plain accordion-item">
                        <div class="card-header" id="headingTwo">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo"> Notre vision </button>
                        </div>
                        <!--/.card-header -->
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="card-body">
                                <p class="text-gray collpase-content">
                                    Devenir un label en terme d’organisation et une référence dans la communauté estudiantine
                                </p>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.accordion-collapse -->
                    </div>
                    <!--/.accordion-item -->
                    <div class="card plain accordion-item">
                        <div class="card-header" id="headingThree">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree"> Notre mission </button>
                        </div>
                        <!--/.card-header -->
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                            <div class="card-body">
                                <p class="text-gray collpase-content">
                                    Promouvoir le Génie Industriel en Algérie, renforcer la formation et le réseau professionnel, et développer la communauté internationale des étudiants et anciens diplômés du GI
                                </p>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.accordion-collapse -->
                    </div>
                    <!--/.accordion-item -->
                </div>
                <!--/.accordion -->
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
        <div class="image-wrapper no-overlay bg-image text-center bg-map mt-n12" data-image-src="{{ URL::asset('assets/img/map.png')}}"></div>
        <div class="row">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Nos Valeurs</h2>
                <h3 class="display-6 lh-lg mb-9">
                    Nous nous engageons à incarner le professionnalisme à travers l'excellence
                </h3>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row gx-md-8 gy-8 mb-16">
            <div class="col-md-6 col-lg-3">
                <div class="icon btn btn-block btn-lg btn-primary pe-none mb-6"> <i class="uil uil-trophy"></i> </div>
                <h4>
                    Engagement
                </h4>
                <p class="mb-3 fs-15 text-gray">
                    Se dévouer à un partage mutuel et propice en y mettant du sien pour la cause IEC et le développement de la communauté GI
                </p>
            </div>
            <!--/column -->
            <div class="col-md-6 col-lg-3">
                <div class="icon btn btn-block btn-lg btn-primary pe-none mb-6"> <i class="uil uil-shield-exclamation"></i> </div>
                <h4>
                    Appartenance
                </h4>
                <p class="mb-3 fs-15 text-gray">
                    Nous croyons en l’importance de l’appartenance au groupe, en travaillant dans un milieu de respect mutuel, de confiance, et de transparence
                </p>
            </div>
            <!--/column -->
            <div class="col-md-6 col-lg-3">
                <div class="icon btn btn-block btn-lg btn-primary pe-none mb-6"> <i class="uil uil-thumbs-up"></i> </div>
                <h4>Intégrité</h4>
                <p class="mb-3 fs-15 text-gray">
                    Nous faisons ce qui est juste et honnête avec sérieux, responsabilité et professionnalisme
                </p>
            </div>
            <!--/column -->
            <div class="col-md-6 col-lg-3">
                <div class="icon btn btn-block btn-lg btn-primary pe-none mb-6"> <i class="uil uil-lightbulb-alt"></i> </div>
                <h4>
                    Innovation
                </h4>
                <p class="mb-3 fs-15 text-gray">
                    Nous n'arrêtons jamais d'apprendre et d’améliorer notre façon de faire avec une vision durable
                </p>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
        <div class="row gy-10 gy-sm-13 gx-lg-3 mb-16 mb-md-18">
            <div class="col-lg-5 position-relative pe-2 pe-md-8">
                <figure class="rounded h-100">
                    <img class="h-100" style="object-fit: cover" src="{{ URL::asset('assets/img/photos/field-1.webp')}}" alt="" />
                </figure>
            </div>
            <!--/column -->
            <div class="col-lg-7">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-2">1er spécialité</h2>
                <h3 class="display-6 lh-lg mb-3">Data Science & Intelligence Artificielle</h3>
                <p class="fs-17 ln-lg text-gray">
                    La DSIA est une spécialité de l'ENP qui a été ouverte en 2020. Elle englobe deux domaines de pointe : la science des données, et l'intelligence artificielle ayant pour but la création de valeur à partir de l'exploration et l'analyse de données brutes grâce à des techniques telles que la programmation informatique, les mathématiques ou les statistiques.
                </p>
                <div class="d-flex mt-6" data-cues="slideInDown" data-group="page-title-buttons" data-delay="300">
                    <span>
                    <a class="btn btn-md btn-primary rounded me-2" href="{{ URL::asset('assets/documents/brochuree.pdf')}}" target="_blank" rel="noopener">Télécharger la brochure</a>
                    </span>
                </div>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
        <div class="row gy-10 gy-sm-13 gx-lg-3 mb-16 mb-md-18">
            <div class="col-lg-5 position-relative pe-0 ps-2 ps-lg-8 order-lg-2">
                <figure class="rounded h-100">
                    <img class="h-100" style="object-fit: cover" src="{{ URL::asset('assets/img/photos/field-2.webp')}}" alt="" />
                </figure>
            </div>
            <!--/column -->
            <div class="col-lg-7 order-lg-1">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-2">2eme spécialité</h2>
                <h3 class="display-6 lh-lg mb-3">Management Industriel</h3>
                <p class="fs-17 ln-lg text-gray">
                    Se situant à l'interface entre les sciences de l'ingénieur, les sciences économiques et les sciences humaines. Ce qui permet à l'ingénieur en MI d'être polyvalent, doté d'un flux d'information nécessaire dans le management des entreprises.
                </p>
                <div class="d-flex mt-6" data-cues="slideInDown" data-group="page-title-buttons" data-delay="300">
                    <span>
                    <a class="btn btn-md btn-primary rounded me-2" href="{{ URL::asset('assets/documents/brochuree.pdf')}}" target="_blank" rel="noopener">Télécharger la brochure</a>
                    </span>
                </div>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
        <div class="row gx-lg-0 gy-10 align-items-center mb-15 mb-lg-18">
            <div class="col-lg-6 order-lg-2 grid offset-lg-1">
                <div class="row gx-md-5 gy-5 align-items-center counter-wrapper isotope">
                    <div class="item col-md-6">
                        <div class="card bg-secondary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-primary pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-user-check"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1">+410</h3>
                                        <p class="mb-0">Alumni</p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card bg-secondary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-primary pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-users-alt"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1">83</h3>
                                        <p class="mb-0">Membres</p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card bg-secondary">
                            <div class="card-body">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-primary pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-calendar-alt"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1">2020</h3>
                                        <p class="mb-0">Création</p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                    <div class="item col-md-6">
                        <div class="card bg-secondary">
                            <div class="card-body ">
                                <div class="d-flex d-lg-block d-xl-flex flex-row">
                                    <div>
                                        <div class="icon btn btn-circle btn-lg btn-primary pe-none mx-auto me-4 mb-lg-3 mb-xl-0"> <i class="uil uil-presentation-check"></i> </div>
                                    </div>
                                    <div>
                                        <h3 class="counter mb-1">12</h3>
                                        <p class="mb-0">Évènements</p>
                                    </div>
                                </div>
                            </div>
                            <!--/.card-body -->
                        </div>
                        <!--/.card -->
                    </div>
                    <!--/column -->
                </div>
                <!--/.row -->
            </div>
            <!--/column -->
            <div class="col-lg-5">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-2">IEC en chiffres</h2>
                <h3 class="display-6 lh-lg mb-3">Nous sommes fiers de nos travaux</h3>
                <p class="fs-19 ln-lg text-gray">
                    Engagé envers l'excellence depuis sa création en 2020, le club IEC a constamment cherché à promouvoir les plus hauts standards de qualité dans toutes ses initiatives et activités.
                </p>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
<section class="wrapper bg-secondary">
    <div class="container py-10">
        <div class="row mb-4">
            <div class="col-lg-9 col-xl-8 col-xxl-7">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Nos Articles</h2>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="swiper-container blog grid-view mb-10" data-margin="30" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach ($posts as $post)
                    <div class="swiper-slide">
                        <article>
                            <a href="{{route('post', ['slug' => $post->slug])}}" class="rounded"> 
                            <img src="{{$post->image->fullpath}}" alt="{{$post->title}}" style="height: 400px; object-fit: cover"/>
                            </a>
                            <div class="post-footer mt-4">
                                <ul class="post-meta">
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i>
                                        <span>{{$post->getFormattedCreatedAt()}}</span>
                                    </li>
                                    <li class="post-comments">
                                        <i class="uil uil-eye fs-15"></i> 
                                        {{$post->visited}}
                                    </li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.post-footer -->
                            <div class="post-header">
                                <h2 class="post-title h3 mt-2">
                                    <a class="text-white clamp-text-2" href="{{route('post', ['slug' => $post->slug])}}">
                                    {{$post->title}}
                                    </a>
                                </h2>
                                <p class="text-gray fs-15 lh-lg post-content clamp-text-3">
                                    {{$post->overview}}
                                </p>
                                <a href="{{route('post', ['slug' => $post->slug])}}" class="more hover link-primary">Lire la suite</a>
                            </div>
                            <!-- /.post-header -->
                        </article>
                        <!-- /article -->
                    </div>
                    <!--/.swiper-slide -->
                    @endforeach
                </div>
                <!-- /.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->
        <div class="d-flex mt-12" data-cues="slideInDown" data-group="page-title-buttons" data-delay="300">
            <span>
            <a class="btn btn-md btn-primary rounded mt-4 me-2" href="{{route('posts')}}">Tous les articles </a>
            </span>
        </div>
    </div>
    <!-- /.container -->
</section>

<!-- /section -->
<section class="wrapper">
    <div class="container py-14">
        <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Nos sponsors et partenaires</h2>
        <div class="row gx-lg-8 mb-10 gy-5">
            <div class="col-lg-6">
                <h3 class="display-5 lh-lg">Ceux qui nous ont déjà fait confiance</h3>
            </div>
            <!-- /column -->
            <div class="col-lg-6">
                <p class="fs-18 lh-lg mb-0 text-gray">
                    Des entreprises dans différents secteurs ont été intéressées par l'ensemble des évènements organisées par notre club.
                </p>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="row">
            <div class="swiper-container mb-10" data-margin="30" data-dots="true" data-items-xl="5" data-items-md="3" data-items-xs="2">
                <div class="swiper overflow-visible pb-2 swiper-initialized swiper-horizontal swiper-backface-hidden">
                    <div class="swiper-wrapper">
                        @foreach ($sponsors as $sponsor)
                        <div class="swiper-slide">
                            <a href="{{$sponsor->url}}" target="_blank" rel="noopener">
                                <div class="col-12">
                                    <div class="card shadow-lg align-items-center bg-secondary" style="height: 150px">
                                        <div class="card-body align-items-center d-flex px-3 py-6 p-md-8">
                                            <figure class="px-md-3 px-xl-0 px-xxl-3 mb-0">
                                                <img src="{{$sponsor->image->fullpath}}" alt="{{$sponsor->name}}" style="height: 75px !important"/>
                                            </figure>
                                        </div>
                                        <!--/.card-body -->
                                    </div>
                                    <!--/.card -->
                                </div>
                            </a>
                        </div>
                        <!--/.swiper-slide -->
                        @endforeach
                    </div>
                    <!--/.swiper-wrapper -->
                </div>
                <!-- /.swiper -->
            </div>
            <!-- /.swiper-container -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper bg-secondary">
    <div class="container py-10">
        <div class="row mb-4">
            <div class="col-lg-9 col-xl-8 col-xxl-7">
                <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Nos Évènements</h2>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
        <div class="swiper-container blog grid-view mb-10 mt-8" data-margin="30" data-dots="true" data-items-xs="1" data-autoplay="true">
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach ($events as $event)
                    <div class="swiper-slide">
                        <div class="row gx-lg-4 gx-xl-8 gy-12 align-items-center">
                            <div class="col-lg-5 position-relative">
                                <div class="img-mask mask-2 px-8">
                                    <img src="{{$event->image->fullpath}}" alt="{{$event->name}}" class="bg-white" style="height: 400px; object-fit: contain"/>
                                </div>
                            </div>
                            <!--/column -->
                            <div class="col-lg-7">
                                <a href="{{route('events')}}">
                                    <h3 class="display-4 mb-5 text-primary">
                                        {{$event->name}}
                                    </h3>
                                </a>
                                <p class="mb-7" style="word-wrap: break-word">
                                    {{$event->description}}
                                </p>
                                <div class="row counter-wrapper gy-6">
                                    <div class="col-md-4">
                                        <h3 class="counter text-primary">
                                            {{$event->editions()->count()}}
                                        </h3>
                                        <p>N0 Éditions</p>
                                    </div>
                                    <!--/column -->
                                </div>
                                <!--/.row -->

                                <a href="{{route('events')}}" class="btn btn-md btn-primary rounded mt-8 me-2">Lire la suite</a>
                            </div>
                            <!--/column -->
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.swiper-slide -->
                    @endforeach
                </div>
                <!-- /.swiper-wrapper -->
            </div>
            <!-- /.swiper -->
        </div>
        <!-- /.swiper-container -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper">
    <div class="container pt-8 pt-md-10 pb-8">
        <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Foire aux questions</h2>

        <div class="row mt-4">
            <div class="col-lg-6 mb-0">
                <div id="accordion-1" class="accordion-wrapper">
                    <div class="card accordion-item bg-secondary">
                        <div class="card-header" id="accordion-heading-1-1">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-1-1" aria-expanded="false" aria-controls="accordion-collapse-1-1">
                                Pourquoi IEC est uniquement ouvert aux étudiants du Génie Industriel?
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div id="accordion-collapse-1-1" class="collapse" aria-labelledby="accordion-heading-1-1" data-bs-target="#accordion-1">
                            <div class="card-body">
                                <p>
                                    La majorité des activités organisées par le club sont ouvertes au grand public, l'organisation des activités est faite par les membres du club, étudiants de la spécialité
                                </p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.collapse -->
                    </div>
                    <!-- /.card -->

                    <div class="card accordion-item bg-secondary">
                        <div class="card-header" id="accordion-heading-1-2">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-1-2" aria-expanded="false" aria-controls="accordion-collapse-1-2">
                                Quelle est la différence entre IEC et les autres clubs de l'école ?
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div id="accordion-collapse-1-2" class="collapse" aria-labelledby="accordion-heading-1-2" data-bs-target="#accordion-2">
                            <div class="card-body">
                                <p>
                                    La différence entre IEC et les autres clubs de l'école réside en premier lieu, dans le fait que IEC est un club qui regroupe uniquement les étudiants du Génie Industriel de l'école avec ses deux sous spécialités. En deuxième lieu, les projets et différents événements organisés par le club répondent aux besoins de la formation de la spécialité
                                </p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.collapse -->
                    </div>
                    <!-- /.card -->

                    
                    <div class="card accordion-item bg-secondary">
                        <div class="card-header" id="accordion-heading-1-3">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-1-3" aria-expanded="false" aria-controls="accordion-collapse-1-3">
                                Les événements faits par IEC, sont-ils pour les étudiants de l’ENP seulement ?
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div id="accordion-collapse-1-3" class="collapse" aria-labelledby="accordion-heading-1-3" data-bs-target="#accordion-3">
                            <div class="card-body">
                                <p>
                                    Non, ils sont ouverts au grand public
                                </p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.collapse -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.accordion-wrapper -->
            </div>
            <!--/column -->
            <div class="col-lg-6">
                <div id="accordion-2" class="accordion-wrapper">
                    <div class="card accordion-item bg-secondary">
                        <div class="card-header" id="accordion-heading-1-4">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-1-4" aria-expanded="false" aria-controls="accordion-collapse-1-4">
                                En quelle année IEC a été créé ?
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div id="accordion-collapse-1-4" class="collapse" aria-labelledby="accordion-heading-1-4" data-bs-target="#accordion-1-4">
                            <div class="card-body">
                                <p>
                                    IEC a été fondé le 7 mars 2020
                                </p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.collapse -->
                    </div>
                    <!-- /.card -->

                    <div class="card accordion-item bg-secondary">
                        <div class="card-header" id="accordion-heading-1-5">
                            <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-1-5" aria-expanded="false" aria-controls="accordion-collapse-1-5">
                                Quels sont les buts d'IEC ?
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div id="accordion-collapse-1-5" class="collapse" aria-labelledby="accordion-heading-1-5" data-bs-target="#accordion-1">
                            <div class="card-body">
                                <p>
                                    Vulgariser les concepts du génie industriel à travers plusieurs événements et activités
                                </p>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.collapse -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.accordion-wrapper -->
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
@endsection
@section('script')
@endsection