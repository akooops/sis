@php
    $footerPrograms = getPrograms();
    $footerMenu = getMenu('footer_primary_menu');

    $emails = json_decode(getSetting('emails')->value);
    $phones = json_decode(getSetting('phones')->value);
    $googleMapsUrl = getSetting('google_maps_url');

    $socialLinks = collect([
        'uil-facebook-f' => getSetting('social_facebook_url'),
        'uil-instagram' => getSetting('social_instagram_url'),
        'uil-x-twitter' => getSetting('social_twitter_url'),
        'uil-youtube' => getSetting('social_youtube_url'),
        'uil-linkedin' => getSetting('social_linkedin_url'),
        'uil-snapchat-ghost' => getSetting('social_snapchat_url'),
        'uil-map-marker' => $googleMapsUrl,
    ])->filter(fn ($setting) => $setting && !empty($setting->value));
@endphp

<footer class="site-footer">
    <div class="container pb-8 pt-12">
        {{-- Two nested grids, matching the original: 2 | 10, with that 10 split
             4 | 4 | 2 | 2. The negative inline margin plus 15px cell padding
             reproduces the old grid's gutters, so the dividers land identically. --}}
        <div class="grid gap-y-8 lg:-mx-[15px] lg:grid-cols-12 lg:gap-y-0">

            <div class="flex justify-center px-[15px] lg:col-span-2 lg:justify-start">
                <div class="site-footer__column">
                    <a href="{{ route('index') }}">
                        <img class="h-16 w-auto" src="{{ URL::asset('assets/img/logo.png') }}"
                            alt="{{ getLanguageKeyLocalTranslation('website_title') }}">
                    </a>
                </div>
            </div>

            <div class="px-[15px] lg:col-span-10">
                <div class="grid gap-y-8 lg:-mx-[15px] lg:grid-cols-12 lg:gap-y-0">

            <div class="px-[15px] text-center lg:col-span-4 lg:text-start">
                <div class="site-footer__column ps-0 lg:ps-8">
                    <ul>
                        @foreach ($footerPrograms as $footerProgram)
                            <li class="mb-1">
                                <a class="text-base font-medium uppercase"
                                    href="{{ route('program', ['slug' => $footerProgram->slug]) }}">
                                    {{ $footerProgram->getLocalTranslation('title') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="px-[15px] text-center lg:col-span-4 lg:text-start">
                <div class="site-footer__column ps-0 lg:ps-8">
                    <h4 class="site-footer__title">
                        {{ getLanguageKeyLocalTranslation('footer_get_in_touch_title') }}
                    </h4>

                    <p class="site-footer__text">
                        @if ($googleMapsUrl && !empty($googleMapsUrl->value))
                            <a href="{{ $googleMapsUrl->value }}" target="_blank" rel="noopener">
                                {{ getLanguageKeyLocalTranslation('footer_address_placeholder') }}
                            </a>
                        @else
                            {{ getLanguageKeyLocalTranslation('footer_address_placeholder') }}
                        @endif

                        <br />

                        @if ($emails && is_array($emails))
                            @foreach ($emails as $email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                                @if (!$loop->last)<br />@endif
                            @endforeach
                        @endif

                        @if ($phones && is_array($phones))
                            @if ($emails && is_array($emails))<br />@endif
                            @foreach ($phones as $phone)
                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $phone) }}" dir="ltr">
                                    {{ $phone }} <i class="uil uil-whatsapp text-[#25D366]" aria-hidden="true"></i>
                                </a>
                                @if (!$loop->last)<br />@endif
                            @endforeach
                        @endif
                    </p>
                </div>
            </div>

            <div class="px-[15px] text-center lg:col-span-2 lg:text-start">
                <div class="site-footer__column ps-0 lg:ps-4">
                    <h4 class="site-footer__title">
                        {{ getLanguageKeyLocalTranslation('footer_first_menu_title') }}
                    </h4>

                    <ul>
                        @if ($footerMenu)
                            @foreach ($footerMenu->items as $footerMenuItem)
                                <li class="mb-1">
                                    <a class="text-lg font-medium capitalize" href="{{ $footerMenuItem->url }}">
                                        {{ $footerMenuItem->getLocalTranslation('title') }}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="px-[15px] text-center lg:col-span-2 lg:text-start">
                <div class="ps-0 lg:ps-4">
                    <h4 class="site-footer__title">
                        {{ getLanguageKeyLocalTranslation('footer_nav_menu_title') }}
                    </h4>

                    <nav class="social-row justify-center lg:justify-start">
                        @foreach ($socialLinks as $icon => $setting)
                            <a href="{{ $setting->value }}" target="_blank" rel="noopener">
                                <i class="uil {{ $icon }}" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>

                </div>
            </div>
        </div>

        <hr class="my-8 border-line" />

        <p class="site-footer__text text-center">
            {{ getLanguageKeyLocalTranslation('footer_all_rights_reserved') }}
        </p>
    </div>
</footer>
