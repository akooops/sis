@php
    $menu = getMenu('header_primary_menu');

    $languages = getLanguages();
    $currentLanguage = getCurrentLanguage();
@endphp

<header class="wrapper">
    <nav class="navbar navbar-expand-lg center-logo position-absolute">
        <div class="container align-items-center">
            <div class="navbar-container d-flex flex-row w-100 justify-content-between align-items-center mt-4 mt-lg-10">
                <div class="col-lg-4 d-none d-lg-flex">
                    <ul class="navbar-nav rounded bg-white">
                        <li class="nav-item dropdown">
                            <a class="nav-link py-2" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" role="button">
                                {{$currentLanguage->name}}
                            </a>

                            <ul class="languages-dropdown dropdown-menu mt-2">
                                @foreach ($languages as $language)
                                    @if($language->code != app()->getLocale())
                                        <li class="nav-item">
                                            <a class="dropdown-item" href="{{route('locale.switch', ['locale' => $language->code])}}">
                                                {{$language->name}}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach  
                            </ul>
                        </li>
                    </ul>
                </div>

                <div class="navbar-brand d-flex col-6 col-lg-4 justify-content-start justify-content-lg-center pe-0 pe-lg-14 py-0">
                    <a href="{{route('index')}}">
                        <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="{{getLanguageKeyLocalTranslation('website_title')}}">                
                    </a>
                </div>

                <div class="navbar-other d-flex col-6 col-lg-4 justify-content-end">
                    <ul class="navbar-nav flex-row align-items-center rounded ps-0 mx-0 ps-md-6 bg-white">
                        <li class="nav-item d-none d-md-block me-6">
                            <a class="nav-link" href="{{route('visits')}}">
                                {{getLanguageKeyLocalTranslation('navbar_visits_nav_link')}}
                            </a>
                        </li>

                        <li class="nav-item d-none d-md-block me-6">
                            <a class="nav-link" href="{{route('inquiries')}}">
                                {{getLanguageKeyLocalTranslation('navbar_inquiries_nav_link')}}
                            </a>
                        </li>

                        <li class="nav-item d-none d-md-block me-6">
                            <a class="nav-link" href="https://eregistration.sis.edu.sa/en-GB/Saud">
                                {{getLanguageKeyLocalTranslation('navbar_applications_nav_link')}}
                            </a>
                        </li>

                        <div class="d-flex bg-primary py-1 navbar-cta-container">
                            <li class="nav-item mx-2">
                                <a class="nav-link text-light px-0" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-nav">
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
            <!-- /.d-flex -->
        </div>
        <!-- /.container -->
    </nav>
    <!-- /.navbar -->

    <div class="navbar-offcanvas-menu offcanvas-nav offcanvas offcanvas-end bg-light" id="offcanvas-nav" data-bs-scroll="true">
        <div class="offcanvas-header pb-2">
            <form class="search-form w-100" action="{{route('articles')}}">
                <input name="search" type="text" class="form-control">
            </form>
            <!-- /.search-form -->

            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column h-100">
            <ul class="navbar-nav">
                @if($menu)
                    @foreach ($menu->items as $menuItem)
                        @if(count($menuItem->children) == 0)
                            <li class="nav-item mt-2">
                                <a class="nav-link py-0" 
                                    href="{{$menuItem->url}}">
                                    {{$menuItem->getLocalTranslation('title')}}
                                </a>
                            </li>
                        @else
                            <div class="accordion accordion-wrapper">
                                <div class="accordion-item py-2">
                                    <div class="card-header p-0" >
                                        <button class="accordion-button collapsed p-0" data-bs-toggle="collapse" data-bs-target="#collapse-menu-item-{{$menuItem->id}}"> 
                                            {{$menuItem->getLocalTranslation('title')}} 
                                        </button>
                                    </div>
                                    <!--/.card-header -->
                                    <div id="collapse-menu-item-{{$menuItem->id}}" class="accordion-collapse collapse mt-2">
                                        @foreach ($menuItem->children as $childMenuItem)
                                            <a class="nav-link text-wrap py-1" 
                                            href="{{$childMenuItem->url}}">
                                            {{$childMenuItem->getLocalTranslation('title')}}
                                        </a>
                                        @endforeach
                                    </div>
                                    <!--/.accordion-collapse -->
                                </div>
                                <!--/.accordion-item -->
                            </div>
                            <!--/.accordion -->
                        @endif
                    @endforeach
                @endif

                @if(count($languages) >= 0)
                    <li class="nav-item mt-2 dropdown">
                        <a class="nav-link dropdown-toggle py-0" href="" data-bs-toggle="dropdown">               
                            {{getLanguageKeyLocalTranslation('navbar_change_language_nav_link')}}
                        </a>

                        <ul class="dropdown-menu py-0">
                            @foreach ($languages as $language)
                                @if($language->code != app()->getLocale())
                                    <a class="dropdown-item" href="{{route('locale.switch', ['locale' => $language->code])}}">
                                        {{$language->name}}
                                    </a>
                                @endif
                            @endforeach  
                        </ul>
                    </li>
                @endif
            </ul>
            <!-- /.navbar-nav -->
        </div>
        <!-- /.offcanvas-body -->
    </div>
    <!-- /.offcanvas -->
</header>
<!-- /header -->