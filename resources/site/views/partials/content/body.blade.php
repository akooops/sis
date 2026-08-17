<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $title }}
        </h2>

        @isset($subtitle)
            <p data-aos="fade-up" data-aos-duration="1000">{{ $subtitle }}</p>
        @endisset

        <hr class="mb-8 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        {{-- `styles` is the record itself when it carries css_url/custom_css.
             Omitted by callers whose model has neither (Program). --}}
        @include('site::partials.content.content-styles', ['model' => $styles ?? null])

        {{-- id="page-content" is the scope hook the admin's own CSS targets. See
             site::partials.content.content-styles. --}}
        <div id="page-content" class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $content !!}
        </div>

        {{ $slot ?? '' }}
    </div>
</section>
