@php
    $headerPrimaryMenu = getMenu('header_primary_menu');
    $ctaMenu = getMenu('cta_menu');
    $servicesMenu = getMenu('services_menu');

    $languages = getLanguages();
@endphp

<header class="site-header" data-sticky-header>
    <div class="container h-full">
        <div class="site-header__inner flex h-full w-full items-center justify-between gap-4">

            {{-- Services dropdown — desktop only, balances the centred logo. --}}
            <div class="hidden basis-1/3 lg:flex">
                @if ($servicesMenu)
                    <div class="dropdown rounded-md bg-paper">
                        <button type="button"
                            class="site-nav-link px-4 py-2"
                            data-toggle="dropdown"
                            data-target="#services-menu"
                            aria-expanded="false"
                            aria-haspopup="true">
                            {{ getLanguageKeyLocalTranslation('header_services_nav_link') }}
                        </button>

                        <ul id="services-menu" class="dropdown-menu" aria-hidden="true">
                            @foreach ($servicesMenu->items as $servicesMenuItem)
                                <li>
                                    <a class="dropdown-item" href="{{ $servicesMenuItem->url }}">
                                        {{ $servicesMenuItem->getLocalTranslation('title') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="flex basis-1/2 justify-start lg:basis-1/3 lg:justify-center lg:pe-14">
                <a href="{{ route('index') }}">
                    <img class="site-header__logo w-auto"
                        src="{{ URL::asset('assets/images/logo-full.png') }}"
                        alt="{{ getLanguageKeyLocalTranslation('website_title') }}">
                </a>
            </div>

            <div class="flex basis-1/2 justify-end lg:basis-1/3">
                <ul class="flex items-center rounded-md bg-paper md:ps-6">
                    @if ($ctaMenu)
                        @foreach ($ctaMenu->items as $ctaMenuItem)
                            <li class="me-6 hidden md:block">
                                <a class="site-cta-link" href="{{ $ctaMenuItem->url }}">
                                    {{ $ctaMenuItem->getLocalTranslation('title') }}
                                </a>
                            </li>
                        @endforeach
                    @endif

                    <li class="flex rounded-sm bg-brand py-1 md:rounded-s-none">
                        <button type="button"
                            class="mx-2 cursor-pointer px-0 text-[22px] leading-[37px] text-mist"
                            data-toggle="drawer"
                            data-target="#nav-drawer"
                            aria-expanded="false"
                            aria-label="{{ getLanguageKeyLocalTranslation('header_search_label') ?: 'Search' }}">
                            <i class="uil uil-search" aria-hidden="true"></i>
                        </button>

                        <button type="button"
                            class="mx-2 cursor-pointer px-0 text-[22px] leading-[37px] text-mist"
                            data-toggle="drawer"
                            data-target="#nav-drawer"
                            aria-expanded="false"
                            aria-label="{{ getLanguageKeyLocalTranslation('header_menu_label') ?: 'Menu' }}">
                            <i class="uil uil-bars" aria-hidden="true"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<div class="drawer-backdrop" data-dismiss="drawer" data-target="#nav-drawer" aria-hidden="true"></div>

<nav id="nav-drawer" class="drawer" aria-hidden="true" aria-label="{{ getLanguageKeyLocalTranslation('website_title') }}">
    <div class="flex items-center px-6 pb-2 pt-6">
        <form class="search-field w-full" action="{{ route('articles') }}">
            <input name="search" type="search" class="input rounded-e-none pe-11" aria-label="Search">
        </form>

        <button type="button" class="drawer-close shrink-0" data-dismiss="drawer" data-target="#nav-drawer"
            aria-label="Close"></button>
    </div>

    <div class="flex h-full flex-col overflow-y-auto px-6 pb-6">
        <ul>
            @if ($headerPrimaryMenu)
                @foreach ($headerPrimaryMenu->items as $headerMenuItem)
                    @if (count($headerMenuItem->children) === 0)
                        <li class="mt-2">
                            <a class="drawer-link" href="{{ $headerMenuItem->url }}">
                                {{ $headerMenuItem->getLocalTranslation('title') }}
                            </a>
                        </li>
                    @else
                        <li class="drawer-group py-2">
                            <button type="button"
                                class="drawer-group-toggle"
                                data-toggle="collapse"
                                data-target="#nav-group-{{ $headerMenuItem->id }}"
                                aria-expanded="false">
                                {{ $headerMenuItem->getLocalTranslation('title') }}
                            </button>

                            <div id="nav-group-{{ $headerMenuItem->id }}" class="collapse-panel drawer-group-panel"
                                aria-hidden="true">
                                <div class="mt-2">
                                    @foreach ($headerMenuItem->children as $childHeaderMenuItem)
                                        <a href="{{ $childHeaderMenuItem->url }}">
                                            {{ $childHeaderMenuItem->getLocalTranslation('title') }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
            @endif
        </ul>

        <div class="mt-auto flex gap-2 pt-8">
            @foreach ($languages as $language)
                <a href="{{ route('locale.switch', ['locale' => $language->code]) }}" aria-label="{{ $language->name }}">
                    <img class="lang-flag" src="{{ $language->flagUrl }}" alt="{{ $language->name }}">
                </a>
            @endforeach
        </div>
    </div>
</nav>
