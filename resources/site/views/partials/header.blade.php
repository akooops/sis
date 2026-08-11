@php
    // aria-current is a comparison against the address bar, so it is computed
    // where it is used rather than by a helper that would only wrap this line.
    $current = rtrim(url()->current(), '/');
@endphp

<header class="site-header" data-sticky-header>
    <div class="container h-full">
        <div class="site-header__inner flex h-full w-full items-center justify-between gap-4">
            <div class="hidden basis-1/3 lg:flex">
                <div class="dropdown rounded-md bg-paper">
                    <button type="button" class="site-nav-link px-4 py-2" data-toggle="dropdown"
                        data-target="#services-menu" aria-expanded="false" aria-haspopup="true">
                        @lang('nav.services')
                    </button>

                    <ul id="services-menu" class="dropdown-menu" aria-hidden="true">
                        @foreach ($site->menuItems('header_services') as $item)
                            <li>
                                @if ($item->url)
                                    <a class="dropdown-item" href="{{ $item->url }}"
                                        @if ($current === rtrim(strtok($item->url, '?'), '/')) aria-current="page" @endif>
                                        {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                    </a>
                                @else
                                    <span class="dropdown-item">
                                        {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="flex basis-1/2 justify-start lg:basis-1/3 lg:justify-center lg:pe-14">
                <a href="{{ route('web.site.home') }}">
                    <img class="site-header__logo w-auto" src="{{ asset('assets/site/images/logo-full.png') }}"
                        alt="@lang('common.site_title')">
                </a>
            </div>

            <div class="flex basis-1/2 justify-end lg:basis-1/3">
                <ul class="flex items-center rounded-md bg-paper md:ps-6">
                    @foreach ($site->menuItems('header_cta') as $item)
                        @if ($item->url)
                            <li class="me-6 hidden md:block">
                                <a class="site-cta-link" href="{{ $item->url }}">
                                    {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                </a>
                            </li>
                        @endif
                    @endforeach

                    <li class="flex rounded-sm bg-brand py-1 md:rounded-s-none">
                        <button type="button" class="mx-2 cursor-pointer px-0 text-[22px] leading-[37px] text-mist"
                            data-toggle="drawer" data-target="#nav-drawer" aria-expanded="false"
                            aria-label="@lang('nav.search.label')">
                            <i class="uil uil-search" aria-hidden="true"></i>
                        </button>

                        <button type="button" class="mx-2 cursor-pointer px-0 text-[22px] leading-[37px] text-mist"
                            data-toggle="drawer" data-target="#nav-drawer" aria-expanded="false"
                            aria-label="@lang('nav.menu.label')">
                            <i class="uil uil-bars" aria-hidden="true"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

@include('site::partials.drawer')
