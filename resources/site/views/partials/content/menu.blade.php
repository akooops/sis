@php($current = rtrim(url()->current(), '/'))

@if ($menu && $menu->rootItems->isNotEmpty())
    <section class="bg-surface">
        {{-- Wraps rather than scrolls: a long menu ran off the side of a phone
             and the items past the edge were only reachable by dragging, with
             nothing on screen to say so. Each item still keeps its own label on
             one line. Desktop is unchanged — the items fit a single row there,
             so the wrap never engages and the row gap never applies. --}}
        <div class="container flex justify-center py-8">
            <ul class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                @foreach ($menu->rootItems as $item)
                    @if ($url = $site->url($item->linkable) ?: $item->url)
                        <li class="whitespace-nowrap">
                            <a class="block py-2 text-base font-semibold uppercase {{ $current === rtrim(strtok($url, '?'), '/') ? 'text-danger' : 'text-brand hover:text-brand-soft' }}"
                                href="{{ $url }}"
                                @if ($current === rtrim(strtok($url, '?'), '/')) aria-current="page" @endif>
                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                            </a>
                        </li>
                    @else
                        <li class="whitespace-nowrap">
                            <span class="block py-2 text-base font-semibold uppercase text-brand">
                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </section>
@endif
