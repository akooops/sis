@php
    $alternates = collect($seo['alternates'] ?? []);

    $current = rtrim(url()->current(), '/');
@endphp

<div class="drawer-backdrop" data-dismiss="drawer" data-target="#nav-drawer" aria-hidden="true"></div>

<nav id="nav-drawer" class="drawer" aria-hidden="true" aria-label="@lang('common.site_title')">
    <div class="flex items-center px-6 pb-2 pt-6">
        {{-- Search is the articles listing's own `search` filter, so there is one
             search implementation rather than a second index to maintain. --}}
        <form class="search-field w-full" action="{{ route('web.site.articles.index') }}">
            <input name="search" type="search" class="input rounded-e-none pe-11"
                value="{{ request()->query('search') }}"
                placeholder="@lang('nav.search.placeholder')" aria-label="@lang('nav.search.label')">
        </form>

        <button type="button" class="drawer-close shrink-0" data-dismiss="drawer" data-target="#nav-drawer"
            aria-label="@lang('common.close')"></button>
    </div>

    <div class="flex h-full flex-col overflow-y-auto px-6 pb-6">
        <ul>
            @foreach ($site->menuItems('header_primary') as $item)
                @if ($item->children->isEmpty())
                    <li class="mt-2">
                        @if ($item->url)
                            <a class="drawer-link" href="{{ $item->url }}"
                                @if ($current === rtrim(strtok($item->url, '?'), '/')) aria-current="page" @endif>
                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                            </a>
                        @else
                            <span class="drawer-link">
                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                            </span>
                        @endif
                    </li>
                @else
                    <li class="drawer-group py-2">
                        <button type="button" class="drawer-group-toggle" data-toggle="collapse"
                            data-target="#nav-group-{{ $item->id }}" aria-expanded="false">
                            {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                        </button>

                        <div id="nav-group-{{ $item->id }}" class="collapse-panel drawer-group-panel"
                            aria-hidden="true">
                            <div class="mt-2">
                                @foreach ($item->children as $child)
                                    @if ($child->url)
                                        <a href="{{ $child->url }}"
                                            @if ($current === rtrim(strtok($child->url, '?'), '/')) aria-current="page" @endif>
                                            {{ $child->getTranslation('title', $site->locale(), true) ?: $child->name }}
                                        </a>
                                    @else
                                        <span>
                                            {{ $child->getTranslation('title', $site->locale(), true) ?: $child->name }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>

        {{-- One enabled language means nothing to switch to. --}}
        @if ($site->languages()->count() > 1)
            <div class="mt-auto flex gap-2 pt-8" aria-label="@lang('nav.language.label')">
                @foreach ($site->languages() as $language)
                    @continue($language->code === $site->locale())

                    {{-- Fall back to that locale's home when the current route
                         cannot be rebuilt for it — an error page rendered outside
                         the site group has no route to translate. --}}
                    <a href="{{ $alternates->get($language->code) ?? url('/'.$language->code) }}"
                        hreflang="{{ $language->code }}" aria-label="{{ $language->name }}">
                        <img class="lang-flag" src="{{ $language->flag_url }}" alt="{{ $language->name }}">
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</nav>
