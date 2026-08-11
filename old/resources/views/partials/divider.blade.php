{{-- Gold rule above the crest, used between the home page sections. --}}
<section class="bg-surface {{ $spacing ?? '' }}">
    <div class="container pb-8">
        <div data-aos="fade-up" data-aos-duration="1000">
            <div class="mx-auto mb-[30px] h-[72px] w-[4px] bg-gold"></div>

            <div class="flex justify-center" data-aos="zoom-in" data-aos-duration="2000">
                <img class="h-[112px] w-auto" src="{{ URL::asset('assets/img/logo.png') }}"
                    alt="{{ getLanguageKeyLocalTranslation('website_title') }}">
            </div>
        </div>
    </div>
</section>
