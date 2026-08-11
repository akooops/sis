{{--
    Standard article-style body: title, rule, then CMS content.

    @param string      $title
    @param string|null $content   Raw HTML from the editor
    @param string|null $subtitle  Optional line under the title
--}}
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

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $content !!}
        </div>

        {{ $slot ?? '' }}
    </div>
</section>
