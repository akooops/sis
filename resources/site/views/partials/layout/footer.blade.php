@php
    $contacts = $site->contacts();

    $emails = $contacts->where('type', 'email');
    $phones = $contacts->where('type', 'phone');
    $whatsapps = $contacts->where('type', 'whatsapp');
    $socials = $contacts->where('type', \App\Models\ContactDetail::SOCIAL_TYPE);

    $address = $contacts->firstWhere('type', \App\Models\ContactDetail::ADDRESS_TYPE);
    $addressText = $address?->getTranslation('address', $site->locale(), true);

    $platforms = \App\Models\ContactDetail::platforms();
@endphp

<footer class="site-footer">
    <div class="container pb-8 pt-12">
        <div class="grid gap-y-8 lg:-mx-[15px] lg:grid-cols-12 lg:gap-y-0">

            <div class="flex justify-center px-[15px] lg:col-span-2 lg:justify-start">
                <div class="site-footer__column">
                    <a href="{{ route('web.site.home') }}">
                        {{-- The crest alone: the full mark is a wide lockup and
                             is unreadable at this size. --}}
                        <img class="h-16 w-auto" src="{{ asset('assets/site/images/logo-mark.png') }}"
                            alt="@lang('common.site_title')">
                    </a>
                </div>
            </div>

            <div class="px-[15px] lg:col-span-10">
                <div class="grid gap-y-8 lg:-mx-[15px] lg:grid-cols-12 lg:gap-y-0">

                    <div class="px-[15px] text-center lg:col-span-4 lg:text-start">
                        <div class="site-footer__column ps-0 lg:ps-8">
                            <ul>
                                @foreach ($site->menuItems('footer_primary') as $item)
                                    <li class="mb-1">
                                        @if ($item->url)
                                            <a class="text-lg font-medium capitalize" href="{{ $item->url }}">
                                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                            </a>
                                        @else
                                            <span class="text-lg font-medium capitalize">
                                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="px-[15px] text-center lg:col-span-4 lg:text-start">
                        <div class="site-footer__column ps-0 lg:ps-8">
                            <h4 class="site-footer__title">@lang('nav.footer.get_in_touch')</h4>

                            <p class="site-footer__text">
                                @if ($addressText)
                                    @if ($address?->map_url)
                                        <a href="{{ $address->map_url }}" target="_blank" rel="noopener">{{ $addressText }}</a>
                                    @else
                                        {{ $addressText }}
                                    @endif
                                    <br>
                                @endif

                                @foreach ($emails as $email)
                                    <a href="mailto:{{ $email->value }}">{{ $email->value }}</a><br>
                                @endforeach

                                {{-- dir="ltr" on the number itself: an E164 string
                                     reads backwards inside an RTL paragraph, and
                                     the surrounding text must stay RTL. --}}
                                @foreach ($phones as $phone)
                                    <a href="tel:{{ $phone->value }}" dir="ltr">{{ $phone->value }}</a><br>
                                @endforeach

                                @foreach ($whatsapps as $whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', (string) $whatsapp->value) }}"
                                        target="_blank" rel="noopener" dir="ltr">
                                        {{ $whatsapp->value }}
                                        <i class="uil uil-whatsapp text-[#25D366]" aria-hidden="true"></i>
                                    </a><br>
                                @endforeach
                            </p>
                        </div>
                    </div>

                    <div class="px-[15px] text-center lg:col-span-2 lg:text-start">
                        <div class="site-footer__column ps-0 lg:ps-4">
                            <h4 class="site-footer__title">@lang('nav.footer.links')</h4>

                            <ul>
                                @foreach ($site->menuItems('footer_secondary') as $item)
                                    <li class="mb-1">
                                        @if ($item->url)
                                            <a class="text-lg font-medium capitalize" href="{{ $item->url }}">
                                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                            </a>
                                        @else
                                            <span class="text-lg font-medium capitalize">
                                                {{ $item->getTranslation('title', $site->locale(), true) ?: $item->name }}
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="px-[15px] text-center lg:col-span-2 lg:text-start">
                        <div class="ps-0 lg:ps-4">
                            <h4 class="site-footer__title">@lang('nav.footer.social')</h4>

                            <nav class="social-row justify-center lg:justify-start">
                                @foreach ($socials as $social)
                                    {{-- The icon is derived from the platform code
                                         through the config registry, so no
                                         admin-supplied text ever lands in a class
                                         attribute. --}}
                                    <a href="{{ $social->value }}" target="_blank" rel="noopener"
                                        aria-label="{{ $platforms[$social->platform]['name'] ?? $social->name }}">
                                        <i class="uil {{ $platforms[$social->platform]['site_icon'] ?? 'uil-link' }}"
                                            aria-hidden="true"></i>
                                    </a>
                                @endforeach

                                @if ($address?->map_url)
                                    <a href="{{ $address->map_url }}" target="_blank" rel="noopener"
                                        aria-label="@lang('nav.footer.address')">
                                        <i class="uil uil-map-marker" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </nav>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <hr class="my-8 border-line">

        <p class="site-footer__text text-center">
            &copy; {{ now()->year }} @lang('common.site_title'). @lang('nav.footer.rights')
        </p>
    </div>
</footer>
