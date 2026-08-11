{{--
    Full-viewport page header: the page artwork behind a dark scrim, with the
    title anchored to the bottom-left. Inner pages used to run this through a
    one-slide carousel; it is a static section now.

    @param string $image  Background image URL
    @param string $title  Heading text
    @param string $level  Heading tag, defaults to h1
--}}
@php
    $level = $level ?? 'h1';
@endphp

<section class="scrim relative h-screen">
    <div class="absolute inset-0 -z-10 bg-cover bg-center bg-no-repeat" data-bg="{{ $image }}"></div>

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
