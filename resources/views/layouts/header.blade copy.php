<header class="wrapper">
    <nav class="navbar navbar-expand-lg classic navbar-bg-dark py-2">
    <div class="container flex-lg-row flex-nowrap align-items-center">
        <div class="navbar-brand me-8">
        <a href="{{route('index')}}">
            <img class="logo-dark" src="{{ URL::asset('assets/img/logo-light.png')}}"/>
            <img class="logo-light" src="{{ URL::asset('assets/img/logo-light.png')}}"/>
        </a>
        </div>
        <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start">
        <div class="offcanvas-header d-lg-none">
            <h3 class="text-white fs-30 mb-0">IEC</h3>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{(
                            request()->routeIs('index')
                        ) ? 'active' : ''}}" href="{{route('index')}}">Acceuil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{(
                        request()->routeIs('index') || 
                        request()->routeIs('index')
                    ) ? 'active' : ''}}"  href="{{route('index')}}">Articles</a>
                </li>   
                <li class="nav-item">
                    <a class="nav-link {{(
                        request()->routeIs('events') || 
                        request()->routeIs('edition')
                    ) ? 'active' : ''}}"  href="{{route('index')}}">Évènements</a>
                </li>  
            </ul>
            <!-- /.navbar-nav -->
            <div class="offcanvas-footer d-lg-none">
            <div>
                <a href="mailto:iec@g.enp.edu.dz" class="text-white">iec@g.enp.edu.dz</a>
                <br /> 00 (123) 456 78 90 <br />
                <nav class="nav social social-white mt-4">
                    <a href="https://www.facebook.com/IEC.ENP"><i class="uil uil-facebook-f"></i></a>
                    <a href="https://www.instagram.com/iec.enp/"><i class="uil uil-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/industrial-engineers-club-iec/mycompany/"><i class="uil uil-linkedin"></i></a>
                </nav>
                <!-- /.social -->
            </div>
            </div>
            <!-- /.offcanvas-footer -->
        </div>
        <!-- /.offcanvas-body -->
        </div>
        <!-- /.navbar-collapse -->
        <div class="navbar-other w-100 d-flex ms-auto">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!--
            <li class="nav-item">
                <a class="btn btn-md btn-outline-primary rounded px-3" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-search">
                    <i class="uil uil-search"></i>
                </a>
            </li>
        -->
            <li class="nav-item d-none d-md-block">
                <a href="{{route('index')}}" class="btn btn-md btn-primary rounded">Nous Contacter</a>
            </li>
            <li class="nav-item d-lg-none">
            <button class="hamburger offcanvas-nav-btn"><span></span></button>
            </li>
        </ul>
        <!-- /.navbar-nav -->
        </div>
        <!-- /.navbar-other -->
    </div>
    <!-- /.container -->
    </nav>
    <!-- /.navbar -->
</header>
<!-- /header -->