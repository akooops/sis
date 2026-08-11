@php
    $level = $level ?? 'h1';
    $heroImage = ($image ?? null) ?: asset('assets/site/images/placeholder.png');
@endphp

<section class="scrim relative h-screen">
    {{-- data-bg rather than an inline style: site/backgrounds.js assigns it after
         load, so the markup carries no URL the CSS has to parse. --}}
    <div class="absolute inset-0 -z-10 bg-cover bg-center bg-no-repeat" data-bg="{{ $heroImage }}"></div>

    {{-- z-3 lifts the title above the scrim, which sits at z-1. --}}
    <div class="container relative z-[3] h-full">
        <div class="flex h-full items-end px-8 pb-24 lg:px-0">
            <div class="w-full lg:ps-4">
                <{{ $level }} class="hero-heading animate-slide-down mb-0 max-w-3xl text-6xl font-semibold uppercase leading-none text-paper lg:text-[55px] lg:leading-[52px]">
                    {{ $title }}
                </{{ $level }}>
            </div>
        </div>
    </div>
</section>
