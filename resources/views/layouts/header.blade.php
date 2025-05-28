<header class="wrapper bg-soft-primary ">
    <nav class="navbar navbar-expand-lg classic position-absolute py-0">
        <div class="container flex-lg-row flex-nowrap align-items-center">
            <div class="navbar-brand w-100">
                <a href="{{route('index')}}">
                    <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="">                
                </a>
            </div>
            <div class="offcanvas offcanvas-nav offcanvas-end">
                <div class="offcanvas-header d-lg-none">
                    <a href="{{route('index')}}">
                        <img src="{{ URL::asset('assets/img/logo.png')}}" alt="" />
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100 d-lg-none">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Dropdown</a>
                            <ul class="dropdown-menu">
                                <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                                <li class="dropdown dropdown-submenu dropend">
                                    <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown">Dropdown</a>
                                    <ul class="dropdown-menu">
                                        <li class="dropdown dropdown-submenu dropend">
                                            <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown">Dropdown</a>
                                            <ul class="dropdown-menu">
                                                <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                                                <li class="nav-item"><a class="dropdown-item" href="#">Another Action</a></li>
                                            </ul>
                                        </li>
                                        <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                                        <li class="nav-item"><a class="dropdown-item" href="#">Another Action</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item"><a class="dropdown-item" href="#">Another Action</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown dropdown-mega">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Mega Menu</a>
                            <ul class="dropdown-menu mega-menu">
                                <li class="mega-menu-content">
                                    <div class="row gx-0 gx-lg-3">
                                        <div class="col-lg-6">
                                            <h6 class="dropdown-header">One</h6>
                                            <div class="row gx-0">
                                                <div class="col-lg-6">
                                                    <ul class="list-unstyled">
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                    </ul>
                                                </div>
                                                <!--/column -->
                                                <div class="col-lg-6">
                                                    <ul class="list-unstyled">
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                        <li><a class="dropdown-item" href="#">Link</a></li>
                                                    </ul>
                                                </div>
                                                <!--/column -->
                                            </div>
                                            <!--/.row -->
                                        </div>
                                        <!--/column -->
                                        <div class="col-lg-3">
                                            <h6 class="dropdown-header">Two</h6>
                                            <ul class="list-unstyled">
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                            </ul>
                                        </div>
                                        <!--/column -->
                                        <div class="col-lg-3">
                                            <h6 class="dropdown-header">Three</h6>
                                            <ul class="list-unstyled">
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                                <li><a class="dropdown-item" href="#">Link</a></li>
                                            </ul>
                                        </div>
                                        <!--/column -->
                                    </div>
                                    <!--/.row -->
                                </li>
                                <!--/.mega-menu-content-->
                            </ul>
                            <!--/.dropdown-menu -->
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Dropdown Large</a>
                            <div class="dropdown-menu dropdown-lg">
                                <div class="dropdown-lg-content">
                                    <div>
                                        <h6 class="dropdown-header">One</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Link</a></li>
                                            <li><a class="dropdown-item" href="#">Link</a></li>
                                            <li><a class="dropdown-item" href="#">Another Link</a></li>
                                        </ul>
                                    </div>
                                    <!-- /.column -->
                                    <div>
                                        <h6 class="dropdown-header">Two</h6>
                                        <ul class="list-unstyled">
                                            <li><a class="dropdown-item" href="#">Link</a></li>
                                            <li><a class="dropdown-item" href="#">Link</a></li>
                                            <li><a class="dropdown-item" href="#">Another Link</a></li>
                                        </ul>
                                    </div>
                                    <!-- /.column -->
                                </div>
                                <!-- /auto-column -->
                            </div>
                        </li>
                    </ul>
                </div>
                <!-- /.offcanvas-body -->
            </div>
            <!-- /.navbar-collapse -->
            <div class="navbar-other ms-lg-4">
                <ul class="navbar-nav flex-row align-items-center ms-auto rounded ps-0 mx-0 ps-md-6">
                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="#">Visit</a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="#">Inquire</a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="#">Apply</a>
                    </li>

                    <div class="d-flex bg-primary py-1 rounded">
                        <li class="nav-item mx-2">
                            <a class="nav-link text-light px-0" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-search">
                                <i class="uil uil-search"></i>
                            </a>
                        </li>

                        <li class="nav-item mx-2">
                            <a class="nav-link text-light px-0" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-nav">
                                <i class="uil uil-bars"></i>
                            </a>
                        </li>
                    </div>

                </ul>
                <!-- /.navbar-nav -->
            </div>
            <!-- /.navbar-other -->
        </div>
        <!-- /.container -->
    </nav>
    <!-- /.navbar -->

    <div class="offcanvas-nav offcanvas offcanvas-end bg-light" id="offcanvas-nav" data-bs-scroll="true">
        <div class="offcanvas-header">
            <a href="{{route('index')}}">
                <img class="logo-canvas" src="{{ URL::asset('assets/img/logo.png')}}" alt="" />
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column h-100">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#">Link example</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Link example dropdown</a>
                    <ul class="dropdown-menu"> 
                        <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                        <li class="dropdown">
                            <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="#">Action</a></li>
                        </li>
                        <li class="nav-item"><a class="dropdown-item" href="#">Another Action</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">Link</a>
                </li>
            </ul>
            <!-- /.navbar-nav -->
        </div>
        <!-- /.offcanvas-body -->
    </div>
    <!-- /.offcanvas -->

    <div class="offcanvas offcanvas-top bg-light" id="offcanvas-search" data-bs-scroll="true" aria-modal="true" role="dialog">
        <div class="container d-flex flex-row py-6">
          <form class="search-form w-100">
            <input id="search-form" type="text" class="form-control" placeholder="Type keyword and hit enter">
          </form>
          <!-- /.search-form -->
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <!-- /.container -->
      </div>
</header>
<!-- /header -->