<header class="wrapper bg-soft-primary">
    <nav class="navbar navbar-expand-lg classic navbar-light navbar-bg-light py-md-2 py-0">
        <div class="container flex-lg-row flex-nowrap align-items-center">
            <div class="navbar-brand w-100">
                <a href="./index.html">
                    <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="">                
                </a>
            </div>
            <div class="offcanvas offcanvas-nav offcanvas-end">
                <div class="offcanvas-header d-lg-none">
                    <a href="./index.html"><img src="./assets/img/logo-light.png" srcset="./assets/img/logo-light@2x.png 2x" alt="" /></a>
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
                    <!-- /.navbar-nav -->
                    <div class="d-lg-none mt-auto pt-6 pb-6 order-4">
                        <a href="mailto:first.last@email.com" class="link-inverse">info@email.com</a>
                        <br /> 00 (123) 456 78 90 <br />
                        <nav class="nav social social-white mt-4">
                            <a href="#"><i class="uil uil-twitter"></i></a>
                            <a href="#"><i class="uil uil-facebook-f"></i></a>
                            <a href="#"><i class="uil uil-dribbble"></i></a>
                            <a href="#"><i class="uil uil-instagram"></i></a>
                            <a href="#"><i class="uil uil-youtube"></i></a>
                        </nav>
                        <!-- /.social -->
                    </div>
                    <!-- /offcanvas-nav-other -->
                </div>
                <!-- /.offcanvas-body -->
            </div>
            <!-- /.navbar-collapse -->
            <div class="navbar-other ms-lg-4">
                <ul class="navbar-nav flex-row align-items-center ms-auto">
                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link" href="#">Visit</a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link" href="#">Inquire</a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link" href="#">Apply</a>
                    </li>

                    <div class="d-flex bg-primary p-1 rounded ms-3">
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
            <a href="./index.html">
                <img class="logo-canvas" src="{{ URL::asset('assets/img/logo-2.png')}}" alt="" />
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