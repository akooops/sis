{{--
    Section navigation for pages that belong to a menu. Scrolls sideways on
    phones rather than wrapping, so the strip stays one line.

    @param \App\Models\Menu $menu
--}}
@if ($menu)
    <section class="bg-surface">
        <div class="container flex justify-center overflow-x-auto py-8">
            <ul class="flex justify-center gap-6">
                @foreach ($menu->items as $menuItem)
                    @php
                        $menuUrl = $menuItem->page
                            ? route('page', ['slug' => $menuItem->page->slug])
                            : $menuItem->url;
                    @endphp

                    <li class="whitespace-nowrap">
                        <a class="block py-2 text-base font-semibold uppercase {{ url()->current() === $menuUrl ? 'text-danger' : 'text-brand hover:text-brand-soft' }}"
                            href="{{ $menuUrl }}"
                            @if (url()->current() === $menuUrl) aria-current="page" @endif>
                            {{ $menuItem->getLocalTranslation('title') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
