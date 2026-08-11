{{--
    Vertical timeline of achievements, one slider per year.

    @param \Illuminate\Support\Collection $achievementsByYear  Keyed by year
    @param string|null $breakpoints  JSON for the per-year slider's breakpoints
--}}
@php
    $breakpoints = $breakpoints ?? '{"992": {"slidesPerView": 2}, "1200": {"slidesPerView": 3}}';
@endphp

<div class="relative">
    @foreach ($achievementsByYear as $year => $yearAchievements)
        <div class="relative flex flex-wrap gap-2 pb-8 md:flex-nowrap md:gap-4 md:pb-14">

            <div class="mb-2 w-full md:mb-0 md:w-[75px] md:shrink-0">
                <span class="badge rounded-sm px-4 py-2 text-sm">{{ $year }}</span>
            </div>

            <div class="relative flex w-[2px] shrink-0 flex-col items-center self-stretch">
                <span class="timeline-dot {{ $loop->first ? '' : 'is-past' }}"></span>
                <span class="timeline-rail"></span>
            </div>

            <div class="min-w-0 flex-1 ps-4 md:ps-0">
                <div class="mb-2 md:hidden">
                    <span class="badge rounded-sm px-4 py-2 text-sm">{{ $year }}</span>
                </div>

                <div class="swiper px-0 lg:px-2"
                    data-swiper='{"loop": true, "spaceBetween": 20, "autoplay": {"delay": 5000, "disableOnInteraction": false}, "breakpoints": {!! $breakpoints !!}}'>

                    <div class="swiper-wrapper px-2 pb-2">
                        @foreach ($yearAchievements as $achievement)
                            <div class="swiper-slide h-auto">
                                <article class="card media-card">
                                    @if ($achievement->thumbnail_url)
                                        <figure class="overlay media-figure">
                                            <a href="{{ route('web.site.achievements.show', ['slug' => $achievement->slug]) }}">
                                                <img src="{{ $achievement->thumbnail_url }}"
                                                    alt="{{ $achievement->getTranslation('title', $site->locale(), true) }}" />
                                            </a>
                                        </figure>
                                    @endif

                                    <div class="card-body">
                                        @if ($achievement->category)
                                            <span class="badge mb-2" style="--badge-color: {{ $achievement->category->color }}">
                                                {{ $achievement->category->getTranslation('title', $site->locale(), true) ?: $achievement->category->name }}
                                            </span>
                                        @endif

                                        <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                            <a class="hover:text-brand-soft"
                                                href="{{ route('web.site.achievements.show', ['slug' => $achievement->slug]) }}">
                                                {{ $achievement->getTranslation('title', $site->locale(), true) }}
                                            </a>
                                        </h2>

                                        <p class="mb-0 text-sm leading-5">
                                            {{ $achievement->getTranslation('description', $site->locale(), true) }}
                                        </p>
                                    </div>

                                    <div class="card-footer">
                                        <ul class="post-meta">
                                            <li>
                                                <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                                {{-- achieved_at is cast to a date, so no parse is needed;
                                                     translatedFormat honours the Carbon locale SetLocale set. --}}
                                                <span>{{ $achievement->achieved_at?->translatedFormat('j M Y') }}</span>
                                            </li>
                                            @if ($achievement->getTranslation('done_by', $site->locale(), true))
                                                <li>
                                                    <i class="uil uil-user" aria-hidden="true"></i>
                                                    <span>{{ $achievement->getTranslation('done_by', $site->locale(), true) }}</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    <div class="swiper-controls">
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Tail marker: closes the rail without starting another row. --}}
    <div class="flex gap-2 md:gap-4">
        <div class="hidden md:block md:w-[75px] md:shrink-0"></div>
        <div class="flex w-[2px] shrink-0 justify-center">
            <span class="timeline-dot is-past h-2 w-2"></span>
        </div>
    </div>
</div>
