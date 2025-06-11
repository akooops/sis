@php
    $menu = getMenu('header_primary_menu');

    $languages = getLanguages();
@endphp

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

                </div>
                <!-- /.offcanvas-body -->
            </div>
            <!-- /.navbar-collapse -->
            <div class="navbar-other ms-lg-4">
                <ul class="navbar-nav flex-row align-items-center ms-auto rounded ps-0 mx-0 ps-md-6">
                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="{{route('visits')}}">
                            {{getLanguageKeyLocalTranslation('navbar_visits_nav_link')}}
                        </a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="{{route('inquiries')}}">
                            {{getLanguageKeyLocalTranslation('navbar_inquiries_nav_link')}}
                        </a>
                    </li>

                    <li class="nav-item d-none d-md-block me-6">
                        <a class="nav-link text-white main-nav-link" href="https://eregistration.sis.edu.sa/en-GB/Saud">
                            {{getLanguageKeyLocalTranslation('navbar_applications_nav_link')}}
                        </a>
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
                @if($menu)
                    @foreach ($menu->items as $menuItem)
                        @if(count($menuItem->children) == 0)
                            <li class="nav-item">
                                <a class="nav-link" 
                                    href="{{
                                        $menuItem->page 
                                            ? route('page', ['slug' => $menuItem->page->slug]) 
                                            : $menuItem->url
                                        }}">
                                    {{$menuItem->getLocalTranslation('title')}}
                                </a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" 
                                    href="{{
                                        $menuItem->page 
                                            ? route('page', ['slug' => $menuItem->page->slug]) 
                                            : $menuItem->url
                                        }}">
                                    {{$menuItem->getLocalTranslation('title')}}
                                    </a>

                                <ul class="dropdown-menu">
                                    @foreach ($menuItem->children as $childMenuItem)
                                        <a class="dropdown-item" 
                                            href="{{
                                                $childMenuItem->page 
                                                    ? route('page', ['slug' => $childMenuItem->page->slug]) 
                                                    : $childMenuItem->url
                                                }}">
                                            {{$childMenuItem->getLocalTranslation('title')}}
                                        </a>
                                    @endforeach  
                                </ul>
                            </li>
                        @endif
                    @endforeach
                @endif

                @if(count($languages) >= 0)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="" data-bs-toggle="dropdown">               
                            {{getLanguageKeyLocalTranslation('navbar_change_language_nav_link')}}
                        </a>

                        <ul class="dropdown-menu">
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

    <div class="offcanvas offcanvas-top bg-light" id="offcanvas-search" data-bs-scroll="true" aria-modal="true" role="dialog">
        <div class="container d-flex flex-row py-6">
          <form class="search-form w-100" action="{{route('articles')}}">
            <input id="search-form" name="search" type="text" class="form-control" placeholder="{{getLanguageKeyLocalTranslation('navbar_search_form_placeholder')}}">
          </form>
          <!-- /.search-form -->
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <!-- /.container -->
      </div>
</header>
<!-- /header -->