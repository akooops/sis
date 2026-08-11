{{--
    Sidebar list: a fixed-size thumbnail beside a title and date. Used for
    "popular articles" and "other achievements from this year".

    @param string   $title  Section heading
    @param iterable $items  Models exposing slug, thumbnailUrl, created_at
    @param string   $route  Route name that takes a slug
--}}
<h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">{{ $title }}</h4>

<ul>
    @foreach ($items as $item)
        <li class="mt-4 flex gap-3 first:mt-0">
            <figure class="h-[85px] w-[70px] shrink-0 overflow-hidden rounded-md">
                <a href="{{ route($route, ['slug' => $item->slug]) }}">
                    <img class="h-full w-full object-cover" src="{{ $item->thumbnailUrl }}"
                        alt="{{ $item->getLocalTranslation('title') }}" />
                </a>
            </figure>

            <div class="min-w-0">
                <h6 class="mb-2">
                    <a class="text-heading hover:text-brand" href="{{ route($route, ['slug' => $item->slug]) }}">
                        {{ $item->getLocalTranslation('title') }}
                    </a>
                </h6>

                <ul class="post-meta">
                    <li>
                        <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                        <span>{{ $item->created_at->format('Y-m-d') }}</span>
                    </li>
                </ul>
            </div>
        </li>
    @endforeach
</ul>
