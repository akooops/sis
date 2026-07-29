@php
    $facilityMenu = $facility->headerMenu();
    $languages = getLanguages();
    $currentLanguage = getCurrentLanguage();
@endphp

<header class="wrapper bg-light">
    <nav class="navbar navbar-expand-lg classic position-relative navbar-light facility-navbar">
        <div class="container flex-lg-row flex-nowrap align-items-center py-4">
            <div class="navbar-brand w-100">
                <a href="{{ facilityRoute('home') }}" class="d-flex align-items-center gap-3 text-decoration-none">
                    @if($facility->logoUrl)
                        <img class="logo" src="{{ $facility->logoUrl }}" alt="{{ transOrDefault($facility, 'title') }}" style="max-height: 56px">
                    @endif
                    <span class="h4 mb-0">{{ transOrDefault($facility, 'title') }}</span>
                </a>
            </div>

            <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-end" id="facility-offcanvas">
                <div class="offcanvas-header d-lg-none">
                    <h3 class="text-white fs-30 mb-0">{{ transOrDefault($facility, 'title') }}</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100">
                    <ul class="navbar-nav">
                        @if($facilityMenu)
                            @foreach ($facilityMenu->items as $menuItem)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ $menuItem->url }}">
                                        {{ transOrDefault($menuItem, 'title') }}
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ facilityRoute('home') }}">
                                    {{ getLanguageKeyLocalTranslation('facility_nav_home') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ facilityRoute('articles') }}">
                                    {{ getLanguageKeyLocalTranslation('facility_nav_articles') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ facilityRoute('albums') }}">
                                    {{ getLanguageKeyLocalTranslation('facility_nav_albums') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ facilityRoute('events') }}">
                                    {{ getLanguageKeyLocalTranslation('facility_nav_events') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ facilityRoute('contact') }}">
                                    {{ getLanguageKeyLocalTranslation('facility_nav_contact') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                    <!-- /.navbar-nav -->
                </div>
                <!-- /.offcanvas-body -->
            </div>
            <!-- /.navbar-collapse -->

            <div class="navbar-other w-100 d-flex ms-auto">
                <ul class="navbar-nav flex-row align-items-center ms-auto">
                    @foreach ($languages as $language)
                        @if(!$currentLanguage || $language->code !== $currentLanguage->code)
                            <li class="nav-item me-3">
                                <a class="nav-link" href="{{ route('locale.switch', $language->code) }}">
                                    {{ strtoupper($language->code) }}
                                </a>
                            </li>
                        @endif
                    @endforeach

                    <li class="nav-item d-none d-md-block me-3">
                        <a href="{{ facilityRoute('reserve') }}" class="btn btn-sm btn-primary rounded">
                            {{ getLanguageKeyLocalTranslation('facility_nav_reserve') }}
                        </a>
                    </li>

                    <li class="nav-item d-lg-none">
                        <button class="hamburger offcanvas-nav-btn" data-bs-toggle="offcanvas" data-bs-target="#facility-offcanvas"><span></span></button>
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
