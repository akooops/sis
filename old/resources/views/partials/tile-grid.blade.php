{{--
    Three-up grid of image cards. Shared by the facilities and brand-identity
    listings. A tile without a `url` renders as a plain, unlinked card.

    @param array  $tiles  [['image','title','tagline','description','url'?], …]
    @param string $cta    Button label, only shown for linked tiles
    @param string $height Optional figure height class, defaults to h-[300px]
--}}
@php
    $height = $height ?? 'h-[300px]';
@endphp

<div class="grid auto-rows-fr gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
    @foreach ($tiles as $tile)
        <article class="card media-card" data-aos="fade-up" data-aos-duration="1000">
            <figure class="overlay {{ $height }}">
                @if (!empty($tile['url']))
                    <a href="{{ $tile['url'] }}">
                        <img class="h-full w-full object-cover" src="{{ $tile['image'] }}" alt="{{ $tile['title'] }}" />
                    </a>
                @else
                    <img class="h-full w-full object-cover" src="{{ $tile['image'] }}" alt="{{ $tile['title'] }}" />
                @endif
            </figure>

            <div class="card-body p-4">
                <h3 class="mb-2">{{ $tile['title'] }}</h3>

                @if (!empty($tile['tagline']))
                    <p class="mb-2 text-brand">{{ $tile['tagline'] }}</p>
                @endif

                <p>{{ $tile['description'] }}</p>

                @if (!empty($tile['url']))
                    <a href="{{ $tile['url'] }}" class="btn btn-sm mt-2 self-start">{{ $cta }}</a>
                @endif
            </div>
        </article>
    @endforeach
</div>
