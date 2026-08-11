@php($current = rtrim(url()->current(), '/'))

@if ($menu && $menu->rootItems->isNotEmpty())
    <section class="bg-surface">
        <div class="container flex justify-center overflow-x-auto py-8">
            <ul class="flex justify-center gap-6">
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
